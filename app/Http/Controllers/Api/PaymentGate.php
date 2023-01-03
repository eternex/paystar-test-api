<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Invoice;
use App\Models\InvoicePaid;
use App\PayStarErrorsHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGate extends Controller
{
    /**
     * This method initialize IPG (get token for IPG) for payment action
     */
    public function requestToPay(Request $request)
    {
        // Get cart.
        $cart = Cart::find($request->cart_id);

        // Save an invoice for payment.
        $invoice = new Invoice();
        $invoice->cart_id = $request->cart_id;
        $invoice->card_number = $request->card_number;
        $invoice->amount = $cart->amount;
        $invoice->save();
        $invoice->refresh(); // TODO: check it and might remove it

        /**
         * Make Signature and Payload for request a IPG token. 
         * Signature struct: amount#order_id#callback
         * Consider that in Signature we use invoice_id instead order_id.
         * In this app order keyword used for store orders of customer.
         */
        $amount_based_iranian_rial =  $cart->amount * 10;
        $signaturePlainText = sprintf("%s#%s#%s", $amount_based_iranian_rial, $invoice->id, env('CALLBACK_PAY_STAR_IPG'));
        $signatureHashed = hash_hmac('sha512', $signaturePlainText, env('PAY_STAR_IPG_SECRET'));

        // Make a http call to request payment.
        $response = Http::withHeaders([
            "Authorization" => "Bearer " . env('PAY_STAR_IPG_GATEWAY_ID'),
            "Content-Type" => "application/json",
        ])->post(env('PAY_STAR_IPG_END_POINT') . '/create', [
            "amount" => $amount_based_iranian_rial,
            "order_id" => $invoice->id,
            "callback" => env('CALLBACK_PAY_STAR_IPG'),
            "sign" => $signatureHashed,
            "card_number" => $request->card_number,
            // To call callback in GET type.
            "callback_method" => 1
        ])->object();

        // Process request.
        $invoice->create_method_status = $response->status;
        if (($response->status == 1)) {
            // Request successed.
            $invoice->payment_amount = $response->data->payment_amount;
            $invoice->ref_num = $response->data->ref_num;
            $invoice->ipg_token = $response->data->token;
        } else {
            /**
             * Error in request for ipg token.
             * Log payload.
             */
            Log::channel('pay_star')->error("Method: /create, InvoiceId: " . $invoice->id, [$response]);
        }
        $invoice->update();

        $payStarErrorsHandler = new PayStarErrorsHandler();
        return response([
            'status' => (($response->status == 1)) ? true : false,
            'token' => $invoice->ipg_token,
            'message' => (($response->status != 1)) ?
                "خطا: " . $payStarErrorsHandler->getFullMessageByErrorCode($response->status) :
                ""
        ], ($response->status == 1) ? 200 : 422);
    }

    /**
     * Get final status of payment by invoice id.
     * 
     * @param Request $request, 
     * @param int $invoice_id 
     * 
     * @return Response
     */
    public function getPayment(Request $request, $invoice_id)
    {
        // Validate recevied invoice_id.
        $invoice = Invoice::find($invoice_id);
        $invoicePaid = InvoicePaid::where('invoice_id', $invoice_id)->get()->first();
        if ($invoicePaid == null) {
            return response([
                'message' => 'Error: Not found this invoice!'
            ], 404);
        }

        // Check payment.
        $_status = $invoicePaid->ipg_response_status == 1 && $invoicePaid->verify_response_status == 1;
        $payStarErrorsHandler = new PayStarErrorsHandler();
        $_message = '';
        if (!$_status) {
            // Error detected in pay.
            if ($invoicePaid->ipg_response_status != 1) {
                $_message = "Error in pay: " . $payStarErrorsHandler->getFullMessageByErrorCode($invoicePaid->ipg_response_status);
            } else {
                // Pay done success but a problem exist in verify.
                if ($invoicePaid->verify_response_status != 0) {
                    // Error in verify action.
                    $_message = "Error in verify: " . $payStarErrorsHandler->getFullMessageByErrorCode($invoicePaid->verify_response_status);
                } else {
                    // Error not same card-number.
                    $_message = "Error in verify: " . $payStarErrorsHandler->getFullMessageByErrorCode(-1000);
                }
            }
        }

        return response([
            "invoice_id" => $invoice_id,
            "tracking_code" => ($invoicePaid->tracking_code == null) ? 'NaN' : $invoicePaid->tracking_code,
            "payment_amount" => number_format($invoice->payment_amount),
            "status" => $_status,
            "message" => $_message
        ]);
    }
}

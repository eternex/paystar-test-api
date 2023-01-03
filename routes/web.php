<?php

use App\Http\Controllers\Api\PaymentGate;
use App\Models\Cart;
use App\Models\cartDetail;
use App\Models\Invoice;
use App\Models\InvoicePaid;
use App\Models\Order;
use App\Models\Paid;
use App\PayStarErrorsHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * The IPG callback page processing.
 */
Route::get('/paystar/callback', function (Request $request) {
    $ipgResponse = $request->all();
    
    /**
     * Bellow fields available in success or fail result.
     * 
     * NOTE:
     * In IPG invoice_id called order_id
     */
    $invoice_id = $ipgResponse['order_id'];
    //$ref_num = $ipgResponse['ref_num'];
    $transaction_id = $ipgResponse['transaction_id'];

        
    // Insert invoice paid.
    $invoicePaid = new InvoicePaid();
    $invoicePaid->invoice_id = $invoice_id;
    $invoicePaid->transaction_id = $transaction_id;
    $invoicePaid->ipg_response_status = $ipgResponse['status'];
    $invoicePaid->save();

    if($ipgResponse['status'] == '1'){
        // Success paid.
        $invoicePaid = InvoicePaid::find($invoice_id);
        $invoicePaid->card_number = $ipgResponse['card_number'];
        $invoicePaid->tracking_code = $ipgResponse['tracking_code'];
        $invoicePaid->update();

        $invoice = Invoice::find($invoice_id);

        // Check that same card number.
        $storedCardNumber = $invoice->card_number;
        for($i=0; $i<6;$i++){
            $storedCardNumber[$i+6] = '*';
        }

        if($storedCardNumber != $invoicePaid->card_number){
            Log::channel('pay_star')->error("Method: /check_same_bank_card_number, InvoiceId: ".$invoice_id, [
                'stored_card_number'=>$invoice->card_number,
                'pay_card_number'=>$invoicePaid->card_number
            ]);
            return redirect()->route('paymentCallBack', ['invoiceId'=>$invoice_id]);
        }
         
        /**
         * Verify Success transaction.
         * Make Signature and Payload for request a IPG token.
         * Signature struct: amount#ref_num#card_number#tracking_code
         */
        $signaturePlainText = sprintf("%s#%s#%s#%s", $invoice->payment_amount, $invoice->ref_num, $invoicePaid->card_number, $invoicePaid->tracking_code);
        $signatureHashed = hash_hmac('sha512', $signaturePlainText, env('PAY_STAR_IPG_SECRET'));
        $payloadRequest = [
            "amount"=> $invoice->payment_amount,
            "ref_num"=> $invoice->ref_num,
            "sign"=> $signatureHashed,
        ];
        
        // Make a http call to request payment.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => env('PAY_STAR_IPG_END_POINT').'/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($payloadRequest),
            CURLOPT_HTTPHEADER => [
              'Authorization: Bearer '.env('PAY_STAR_IPG_GATEWAY_ID'),
              'Content-Type: application/json'
            ],
        ]);
        $responseVerify = curl_exec($curl);
        $responseVerify = json_decode($responseVerify);
        curl_close($curl);

        // Update Veryfy status.
        $invoicePaid = InvoicePaid::find($invoice_id);
        $invoicePaid->verify_response_status = $responseVerify->status;
        $invoicePaid->verify_response_time = Carbon::now()->toDateTimeString();
        $invoicePaid->update();

        if($responseVerify->status != '1'){
            // Failed in verify.
            Log::channel('pay_star')->error("Method: /verify, InvoiceId: ".$invoice_id, [$responseVerify]);        
        }
    }else{
        // Failed in pay.
        Log::channel('pay_star')->error("Method: /return_from_ipg, InvoiceId: ".$invoice_id, [$ipgResponse]);
    }

    return redirect()->route('paymentCallBack', ['invoiceId'=>$invoice_id]);
});

/**
 * The callback page UI processing.
 */
Route::get('/paystar/callback/{invoiceId}', function(Request $request,$invoice_id){
    $paymentGate  = new PaymentGate();
    $payment = $paymentGate->getPayment($request, $invoice_id);
    
    $ChargeResponse = collect($payment);
    if($ChargeResponse["\x00*\x00statusCode"] != 200){
        return abort(404);
    }
    
    return view("pay_callback", [
        "invoice_id"=>$invoice_id,
        "tracking_code"=>($ChargeResponse['original']['tracking_code'] == null)?'NaN':$ChargeResponse['original']['tracking_code'],
        "payment_amount"=>$ChargeResponse['original']['payment_amount'],
        "status"=>$ChargeResponse['original']['status'],
        "message"=>$ChargeResponse['original']['message']
    ]);

})->name('paymentCallBack');


Route::get('/reset-all', function(){
    Order::truncate();
    Cart::truncate();
    cartDetail::truncate();
    Invoice::truncate();
    InvoicePaid::truncate();
    return response(['All tables cleaned.']);
});

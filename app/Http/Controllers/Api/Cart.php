<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart as ModelsCart;
use App\Models\cartDetail;
use App\Models\Order;
use Illuminate\Http\Request;

class Cart extends Controller
{
    /**
     * This method make a fake cart to achive an amount to pay.
     */
    public function getNewFakeCart()
    {

        /**
         * The prices and names are two list that used randomly to make a cart.
         */
        $prices = [
            500,
            550,
            600,
            650,
            750,
            850,
        ];

        $names = [
            'Mobile A13',
            'Print HP',
            'Keyboard T22',
            'Convertor VGA',
            'USB Cable',
        ];

        // Make a new order.
        $order = new Order();
        $order->save();

        // Make a new cart.
        $cart = new ModelsCart();
        $cart->order_id = $order->id;
        $cart->save();

        // make a fake cart for created order.
        $cart_detail = [];
        for ($i = 0; $i < rand(1, env('MAX_PRODUCT_COUNT_POSSIBILITY')); $i++) {
            $_product_price = $prices[rand(0, count($prices) - 1)];
            $_product_quantity = rand(1, 1);
            $cart_detail[] = [
                'cart_id' => $cart->id,
                'product_name' => $names[rand(0, count($names) - 1)],
                'product_price' => $_product_price,
                'product_quantity' => $_product_quantity
            ];
            $cart->amount += $_product_quantity * $_product_price;
        }
        $cart->update();
        cartDetail::insert($cart_detail);

        return response([
            'order_id' => $order->id,
            'cart_id' => $cart->id,
            'cart' => $cart_detail,
            'amount' => $cart->amount
        ]);
    }
}

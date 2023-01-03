<?php

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cart::class);
            $table->string('card_number', 16);
            $table->integer('amount');
            
            // Filled by /create Ipg Method. 
            $table->integer('create_method_status')->default(0);
            $table->integer('payment_amount')->nullable();
            $table->string('ref_num', 32)->nullable();
            $table->string('ipg_token', 128)->nullable();

            $table->timestamps();
            
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};

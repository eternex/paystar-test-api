<?php

use App\Models\Invoice;
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
        Schema::create('invoice_paids', function (Blueprint $table) {
            $table->bigInteger('invoice_id');
            $table->primary('invoice_id');
            $table->string('transaction_id', 30);
            
            // Fill in case Success-Pay
            $table->integer('ipg_response_status')->default(0);
            $table->string('card_number', 16)->nullable();
            $table->string('tracking_code', 30)->nullable();

            // Fill by /verify Ipg Method.
            $table->integer('verify_response_status')->default(0);
            $table->timestamp('verify_response_time')->nullable();


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
        Schema::dropIfExists('invoice_paids');
    }
};

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_product_deal', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('minimum_order_quantity')->unsigned();
            $table->integer('product_now_id')->unsigned();
            $table->integer('offer_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['minimum_order_quantity', 'product_now_id', 'offer_id']);

            $table->foreign('product_now_id')->references('id')->on('tbl_product_now')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('offer_id')->references('id')->on('tbl_offer')->onUpdate('cascade')->onDelete('cascade');
        });
        
        //Remove deals table
        Schema::dropIfExists('tbl_deal'); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_product_deal');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorSubscriptionProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendor_subscription_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_subscription_id');
            $table->unsignedBigInteger('mobile_id');
            $table->foreign('vendor_subscription_id')->references('id')->on('vendor_subscriptions')->onDelete('cascade');
            $table->foreign('mobile_id')->references('id')->on('vendor_mobiles')->onDelete('cascade');
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
        Schema::dropIfExists('vendor_subscription_products');
    }
}

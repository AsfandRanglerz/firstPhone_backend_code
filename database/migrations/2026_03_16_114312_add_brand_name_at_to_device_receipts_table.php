<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBrandNameAtToDeviceReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('device_receipts', function (Blueprint $table) {
            $table->string('brand_name')->nullable();
            $table->string('model_name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('storage')->nullable();
            $table->string('price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('device_receipts', function (Blueprint $table) {
            //
        });
    }
}

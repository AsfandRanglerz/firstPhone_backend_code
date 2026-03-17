<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductAtToOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('brand_name')->nullable()->after('vendor_id');
            $table->string('model_name')->nullable()->after('brand_name');
            $table->string('location')->nullable()->after('model_name');
            $table->string('latitude')->nullable()->after('location');
            $table->string('longitude')->nullable()->after('latitude');
            $table->string('storage')->nullable()->after('longitude');
            $table->string('ram')->nullable()->after('storage');
            $table->string('color')->nullable()->after('ram');
            $table->string('condition')->nullable()->after('color');
            $table->string('processor')->nullable()->after('condition');
            $table->string('display')->nullable()->after('processor');
            $table->string('charging')->nullable()->after('display');
            $table->string('refresh_rate')->nullable()->after('charging');
            $table->string('main_camera')->nullable()->after('refresh_rate');
            $table->string('ultra_camera')->nullable()->after('main_camera');
            $table->string('telephoto_camera')->nullable()->after('ultra_camera');
            $table->string('front_camera')->nullable()->after('telephoto_camera');
            $table->string('build')->nullable()->after('front_camera');
            $table->string('wireless')->nullable()->after('build');
            $table->string('stock')->nullable()->after('wireless');
            $table->string('sold')->nullable()->after('stock');
            $table->string('pta_approved')->nullable()->after('sold');
            $table->string('ai_features')->nullable()->after('pta_approved');
            $table->string('battery_health')->nullable()->after('ai_features');
            $table->string('os_version')->nullable()->after('battery_health');
            $table->string('warranty_start')->nullable()->after('os_version');
            $table->string('warranty_end')->nullable()->after('warranty_start');
            $table->longText('image')->nullable()->after('warranty_end');
            $table->longText('video')->nullable()->after('image');
            $table->longText('about')->nullable()->after('video');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            //
        });
    }
}

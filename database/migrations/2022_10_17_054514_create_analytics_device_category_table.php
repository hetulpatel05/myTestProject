<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalyticsDeviceCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('analytics_device_category', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('analytics_user_id')->index();
            $table->integer('desktop')->nullable()->comment('Desktop Devices');
            $table->integer('mobile')->nullable()->comment('Mobile Devices');
            $table->integer('tablet')->nullable()->comment('Tablet Devices');
            $table->date('from_date')->comment('Records Range')->nullable();
            $table->date('to_date')->comment('Records Range')->nullable();
            $table->integer('week_number')->comment('current week number')->nullable();
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
        Schema::dropIfExists('analytics_device_category');
    }
}

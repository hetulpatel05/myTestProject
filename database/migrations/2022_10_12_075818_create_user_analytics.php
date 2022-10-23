<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAnalytics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_analytics', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->integer('campaign_id')->nullable();
            $table->string('token',255)->nullable()->comment('Google access token');
            $table->string('refresh_token',255)->nullable()->comment('Google refresh token');
            $table->bigInteger('google_id')->nullable()->comment('Google login Id');
            $table->integer('account_id')->comment('Google analytics Account ID');
            $table->string('account_name',255)->nullable()->comment('Google analytics Account Name');
            $table->string('property_id')->nullable()->comment('Analytics Property Id');
            $table->string('property_name',255)->nullable()->comment('Analytics Property Name');
            $table->integer('profile_id')->nullable()->comment('Analytics Profile(View) Id');
            $table->string('profile_name',255)->nullable()->comment('Analytics Profile(View) Name');
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
        Schema::dropIfExists('user_analytics');
    }
}
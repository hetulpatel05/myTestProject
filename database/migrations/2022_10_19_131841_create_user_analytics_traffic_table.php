<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAnalyticsTrafficTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_analytics_traffics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('analytics_user_id')->index();
            $table->date('traffic_date')->comment('Traffic Date');
            $table->integer('users')->comment('Number of Users');            
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
        Schema::dropIfExists('user_analytics_traffic');
    }
}

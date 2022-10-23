<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalyticsDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('analytics_data', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('analytics_user_id')->index();
            $table->integer('number_of_users')->comment('Total Users');
            $table->integer('number_of_newusers')->comment('New Users');
            $table->integer('revenue');
            $table->float('bounce_rate')->comment('Bounce rate in percentage');
            $table->float('session_duration')->comment('session duration in Seconds');
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
        Schema::dropIfExists('analytics_data');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashflowSubsidiaryPlacementsUsdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashflow_subsidiary_placements_usd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('cpty')->nullable();
            $table->string('trade_id')->nullable(); 
            $table->double('open_nominal')->nullable();
            $table->string('rate')->nullable(); 
            $table->string('cashflow_days')->nullable();
            $table->date('start_date')->nullable(); 
            $table->date('end_date')->nullable(); 
            $table->date('maturity_date')->nullable(); 
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
        Schema::dropIfExists('cashflow_subsidiary_placements_usd');
    }
}

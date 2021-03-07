<?php

use clayliddell\ShoppingCart\Database\Migrations\CartMigrationBase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConditionValidatorsTable extends CartMigrationBase
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('condition_validators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('validator');
            $table->timestamps();

            $table->bigInteger('type_id')->unsigned();
            $table->foreign('type_id')->references('id')->on('condition_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('condition_validators');
    }
}

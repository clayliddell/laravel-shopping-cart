<?php

use clayliddell\ShoppingCart\Database\Migrations\CartMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemConditionsTable extends CartMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('item_conditions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->double('value', 8, 2);
            $table->boolean('stacks');
            $table->timestamps();

            $table->bigInteger('item_id')->unsigned();
            $table->foreign('item_id')->references('id')->on('items');
            $table->bigInteger('type_id')->unsigned();
            $table->foreign('type_id')->references('id')->on('item_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('item_conditions');
    }
}

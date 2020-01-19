<?php

use clayliddell\ShoppingCart\Database\Migrations\CartMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartConditionsTable extends CartMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('cart_conditions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->decimal('value', 8, 2);
            $table->boolean('percentage')->default(false);
            $table->timestamps();

            $table->bigInteger('cart_id')->unsigned();
            $table->foreign('cart_id')->references('id')->on('carts');
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
        Schema::connection($this->connection)->dropIfExists('cart_conditions');
    }
}

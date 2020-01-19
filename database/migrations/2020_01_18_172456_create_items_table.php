<?php

use clayliddell\ShoppingCart\Database\Migrations\CartMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends CartMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->double('price', 8, 2);
            $table->integer('quantity');
            $table->timestamps();

            $table->bigInteger('cart_id')->unsigned();
            $table->foreign('cart_id')->references('id')->on('carts');
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
        Schema::connection($this->connection)->dropIfExists('items');
    }
}

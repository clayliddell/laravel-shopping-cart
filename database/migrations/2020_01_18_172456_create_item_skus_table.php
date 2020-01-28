<?php

use clayliddell\ShoppingCart\Database\Migrations\CartMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemSkusTable extends CartMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('item_skus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sku');
            $table->double('price', 8, 2);
            $table->timestamps();

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
        Schema::connection($this->connection)->dropIfExists('item_skus');
    }
}

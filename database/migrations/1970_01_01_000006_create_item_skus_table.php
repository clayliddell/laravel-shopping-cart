<?php

use clayliddell\ShoppingCart\Database\Migrations\CartMigrationBase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemSkusTable extends CartMigrationBase
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
            $table->string('name');
            $table->double('price', 8, 2);
            $table->string('details');
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

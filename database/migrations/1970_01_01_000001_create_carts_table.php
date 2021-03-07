<?php

use clayliddell\ShoppingCart\Database\Migrations\CartMigrationBase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends CartMigrationBase
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('carts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session');
            $table->string('instance');
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
        Schema::connection($this->connection)->dropIfExists('carts');
    }
}

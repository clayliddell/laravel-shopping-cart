<?php

use clayliddell\ShoppingCart\Database\Migrations\CartMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConditionTypesTable extends CartMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('condition_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->decimal('value', 8, 2);
            $table->boolean('stacks')->default(false);
            $table->boolean('percentage')->default(false);
            $table->timestamps();

            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('condition_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('condition_types');
    }
}

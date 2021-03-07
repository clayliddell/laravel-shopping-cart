<?php

namespace clayliddell\ShoppingCart\Database\Migrations;

use Illuminate\Database\Migrations\Migration;

abstract class CartMigrationBase extends Migration
{
    /**
     * The name of the database connection to use.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        // Set the connection to be used for this migration to whatever
        // connection is set in the shopping cart config file.
        $this->connection = config('shopping_cart.connection');
    }
}

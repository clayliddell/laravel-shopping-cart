<?php

namespace clayliddell\ShoppingCart\Database\Migrations;

use Illuminate\Database\Migrations\Migration;

class CartMigration extends Migration
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
        // If a constructor is ever added to the stock Laravel Migration, call
        // it during construction.
        if (is_callable('parent::__construct')) {
            parent::__construct();
        }
        // Set the connection to be used for this migration to whatever
        // connection is set in the shopping cart config file.
        $this->connection = config('shopping_cart.connection');
    }
}

<?php

namespace clayliddell\ShoppingCart\Database\Seeds;

use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
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
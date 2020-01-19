<?php

namespace clayliddell\ShoppingCart\Database\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Shopping cart item base model.
 */
abstract class CartBase extends Model
{
    /**
     * DB connection name to be used for model.
     *
     * @var string
     */
    protected $connection;

    /**
     * Shopping cart item validation rules.
     *
     * @var array
     */
    public static $rules = [];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        // Call parent constructor.
        parent::__construct();
        // Set the connection to be used for this migration to whatever
        // connection is set in the shopping cart config file.
        $this->connection = config('shopping_cart.connection');
    }
}

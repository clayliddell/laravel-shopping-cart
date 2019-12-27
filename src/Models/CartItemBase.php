<?php

namespace clayliddell\ShoppingCart\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Shopping cart item base model.
 */
abstract class CartItemBase extends Model
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
        $this->connection = config('shopping_cart.connection');
    }
}

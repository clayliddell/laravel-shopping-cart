<?php

namespace clayliddell\ShoppingCart\Models;

/**
 * Shopping cart item container.
 */
class CartItemCondition extends CartItemBase
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
    public static $rules = [
        'name' => 'required|string',
        'type' => 'required|string',
        'value' => 'required|numeric',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'value',
    ];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->connection = config('shopping_cart.connection');
    }
}

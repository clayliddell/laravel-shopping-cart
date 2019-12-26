<?php

namespace clayliddell\ShoppingCart\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Shopping cart item container.
 */
class CartItem extends Model
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
        'session_id' => 'required|string',
        'item_id' => 'required|string',
        'name' => 'required|string',
        'price' => 'required|numeric',
        'quantity' => 'required|numeric|min:1',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_id',
        'item_id',
        'name',
        'price',
        'quantity',
    ];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->connection = config('shopping_cart.connection');
    }
}

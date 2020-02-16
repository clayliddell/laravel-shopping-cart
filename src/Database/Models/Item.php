<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart item container.
 */
class Item extends CartBase
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array
     */
    public static $rules = [
        'cart_id'       => 'required|exists:$connection.carts,id',
        'sku_id'        => 'required|exists:$connection.item_skus,id',
        'attributes_id' => 'nullable|exists:$connection.item_attributes,id',
        'quantity'      => 'required|integer|min:1',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cart_id',
        'sku_id',
        'attributes_id',
        'quantity',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array
     */
    protected $with = [
        'sku',
        'conditions',
        'attributes',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function sku()
    {
        return $this->belongsTo(ItemSku::class);
    }

    public function conditions()
    {
        return $this->hasMany(ItemCondition::class);
    }

    public function attributes()
    {
        $model = config('shopping_cart.cart_item_attributes_model', 'App\ItemAttributes');
        return $this->belongsTo($model);
    }
}

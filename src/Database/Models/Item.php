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
        'cart_id'       => 'required|numeric',
        'sku_id'        => 'required|numeric',
        'attributes_id' => 'numeric',
        'name'          => 'required|string',
        'quantity'      => 'required|numeric|min:1',
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
        'name',
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
        return $this->hasOne(ItemAttributes::class);
    }
}

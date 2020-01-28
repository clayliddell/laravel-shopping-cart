<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart item sku.
 */
class ItemSku extends CartBase
{
    /**
     * @inheritDoc validation rules.
     *
     * @var array
     */
    public static $rules = [
        'sku'     => 'required|string',
        'price'   => 'required|numeric',
        'type_id' => 'required|numeric',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sku',
        'price',
        'type_id',
    ];

    /**
     * Get item type associated with this sku.
     *
     * @return void
     */
    public function type()
    {
        return $this->belongsTo(ItemType::class);
    }

    /**
     * Get all items which are of this sku.
     *
     * @return void
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }
}

<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart item sku.
 */
class ItemSku extends CartBase
{
    /**
     * {@inheritDoc} validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name'    => 'required|string',
        'price'   => 'required|numeric',
        'type_id' => 'required|exists:$connection.item_type,id',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'price',
        'type_id',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array
     */
    protected $with = [
        'type',
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

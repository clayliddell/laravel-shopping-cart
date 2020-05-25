<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart condition.
 */
class Condition extends CartBase
{
    /**
     * {@inheritDoc} validation rules.
     *
     * @var array<string>
     */
    public static $rules = [
        'cart_id' => 'required_without:item_id|exists:$connection.carts,id',
        'item_id' => 'required_without:cart_id|exists:$connection.items,id',
        'type_id' => 'required|integer',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'cart_id',
        'item_id',
        'type_id',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array<string>
     */
    protected $with = [
        'type',
    ];

    /**
     * Get cart which this condition belongs to.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get item which this condition belongs to.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get type which this condition belongs to.
     */
    public function type()
    {
        return $this->belongsTo(ConditionType::class);
    }
}

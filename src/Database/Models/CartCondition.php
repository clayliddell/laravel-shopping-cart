<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart condition.
 */
class CartCondition extends ConditionBase
{
    /**
     * Shopping cart condition validation rules.
     *
     * @var array
     */
    public static $rules = [
        'cart_id'      => 'required|exists:$connection.carts,id',
        'type_id'      => 'required|exists:$connection.condition_types,id',
        'name'         => 'required|string',
        'value'        => 'required|numeric',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cart_id',
        'type_id',
        'name',
        'value',
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
     * Get the item associated with this condition.
     *
     * @return void
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the type associated with this condition.
     *
     * @return void
     */
    public function type()
    {
        return $this->belongsTo(ConditionType::class);
    }
}

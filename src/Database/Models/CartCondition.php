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
        'cart_id'      => 'required|numeric',
        'type_id'      => 'required|numeric',
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
     * Get the item associated with this condition.
     *
     * @return void
     */
    public function cart()
    {
        return $this->belongsTo('Model\Cart');
    }

    /**
     * Get the type associated with this condition.
     *
     * @return void
     */
    public function type()
    {
        return $this->belongsTo('Model\ConditionType');
    }
}

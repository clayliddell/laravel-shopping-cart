<?php

namespace clayliddell\ShoppingCart\Models;

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
        'value'        => 'required|numeric|if:percentage,true,==,max:1',
        'percentage'   => 'required|boolean',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'condition_id',
        'type_id',
        'value',
        'percentage',
    ];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
    }

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

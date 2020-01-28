<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart condition type.
 */
class ConditionType extends CartBase
{
    /**
     * @inheritDoc validation rules.
     *
     * @var array
     */
    public static $rules = [
        'type' => 'required|string',
        'percentage' => 'required|boolean',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'percentage',
    ];

    /**
     * Get all item conditions which are of this condition type.
     *
     * @return void
     */
    public function itemConditions()
    {
        return $this->hasMany(ItemCondition::class);
    }

    /**
     * Get all item conditions which are of this condition type.
     *
     * @return void
     */
    public function cartConditions()
    {
        return $this->hasMany(CartCondition::class);
    }
}

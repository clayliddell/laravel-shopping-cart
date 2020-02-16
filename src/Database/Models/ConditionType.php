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
        'name'       => 'required|string',
        'category'   => 'required|string',
        'percentage' => 'nullable|boolean',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'category',
        'percentage',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array
     */
    protected $with = [
        'category',
    ];

    /**
     * Get category which this condition type belongs to.
     *
     * @return void
     */
    public function category()
    {
        return $this->belongsTo(ConditionCategory::class);
    }

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

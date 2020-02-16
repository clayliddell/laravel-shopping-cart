<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart condition category.
 */
class ConditionCategory extends CartBase
{
    protected $table = 'condition_categories';

    /**
     * @inheritDoc validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name'       => 'required|string',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get all condition types which are of this category.
     *
     * @return void
     */
    public function conditionTypes()
    {
        return $this->hasMany(ConditionType::class);
    }
}

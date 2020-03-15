<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart condition category.
 */
class ConditionCategory extends CartBase
{
    protected $table = 'condition_categories';

    /**
     * {@inheritDoc} validation rules.
     *
     * @var array<string>
     */
    public static $rules = [
        'name' => 'required|string',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get all condition types which are of this condition category.
     */
    public function types()
    {
        return $this->hasMany(ConditionType::class);
    }
}

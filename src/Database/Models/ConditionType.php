<?php

namespace clayliddell\ShoppingCart\Database\Models;

use clayliddell\ShoppingCart\Interfaces\Database\HasConditions;

/**
 * Shopping cart condition type.
 */
class ConditionType extends CartBase
{
    /**
     * {@inheritDoc} validation rules.
     *
     * @var array<string>
     */
    public static $rules = [
        'category_id' => 'required|integer',
        'name'        => 'required|string',
        'percentage'  => 'nullable|boolean',
        'stacks'      => 'nullable|boolean',
        'value'       => 'required|numeric',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'percentage',
        'stacks',
        'value',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array<string>
     */
    protected $with = [
        'category',
        'validators',
    ];

    /**
     * Get category which this condition type belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ConditionCategory::class);
    }

    /**
     * Get all condition validators this condition type has.
     */
    public function validators()
    {
        return $this->hasMany(ConditionValidator::class, 'type_id');
    }

    /**
     * Evaluate whether this condition type is applicable to supplied entity.
     *
     * @param HasConditions $cartEntity
     *   Cart entity being validated; must be condition-able.
     *
     * @return bool
     *   Whether the cart entity passed validation.
     */
    public function validate(HasConditions $cartEntity): bool
    {
        // Iterate through each of this condition type's validation rules and
        // ensure the current cart entity meets the requirements.
        return $this->validators->reduce(
            fn ($success, $validator) => $success && $validator->validate($cartEntity),
            true
        );
    }
}

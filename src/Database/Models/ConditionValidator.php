<?php

namespace clayliddell\ShoppingCart\Database\Models;

use clayliddell\ShoppingCart\Database\Interfaces\HasConditions;

/**
 * Shopping cart condition validator.
 */
class ConditionValidator extends CartBase
{
    /**
     * @inheritDoc validation rules.
     *
     * @var array<string>
     */
    public static $rules = [
        'type_id'   => 'required|integer',
        'validator' => 'required|string',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'type_id',
        'validator',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array<string>
     */
    protected $with = [];

    /**
     * Evaluate whether this condition is applicable to the supplied entity.
     *
     * @param HasConditions $cartEntity
     *   Cart entity being validated; must be condition-able.
     *
     * @return bool
     *   Whether the cart entity passed validation.
     */
    public function validate(HasConditions $cartEntity): bool
    {
        return $this->validator($cartEntity);
    }
}

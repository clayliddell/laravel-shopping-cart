<?php

namespace clayliddell\ShoppingCart\Database\Models;

use clayliddell\ShoppingCart\Database\Interfaces\HasConditions;

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
     * @var array
     */
    protected $fillable = [
        'cart_id',
        'item_id',
        'type_id',
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
     * Get cart which this condition belongs to.
     *
     * @return void
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get item which this condition belongs to.
     *
     * @return void
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get type which this condition belongs to.
     *
     * @return void
     */
    public function type()
    {
        return $this->belongsTo(ConditionType::class);
    }

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
        // Iterate through each of this condition type's validation rules and
        // ensure the current cart entity meets the requirements.
        return $this->type->validators->reduce(
            fn ($success, $validator) => $success && $validator($cartEntity),
            true
        );
    }
}

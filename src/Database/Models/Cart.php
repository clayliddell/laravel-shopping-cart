<?php

namespace clayliddell\ShoppingCart\Database\Models;

use clayliddell\ShoppingCart\Database\Interfaces\HasConditions;

class Cart extends CartBase implements HasConditions
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array<string>
     */
    public static $rules = [
        'session'  => 'required|string',
        'instance' => 'required|string',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'session',
        'instance',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array<string>
     */
    protected $with = [
        'items',
        'conditions',
    ];

    /**
     * Get the items associated with this cart.
     *
     * @return void
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the conditions associated with this cart.
     *
     * @return void
     */
    public function conditions()
    {
        return $this->hasMany(Condition::class);
    }

    /**
     * Clear all shopping cart items and conditions.
     *
     * @return void
     */
    public function clear()
    {
        $this->clearItems();
        $this->clearCartConditions();
    }

    /**
     * Remove all items from cart.
     *
     * @return void
     */
    public function clearItems()
    {
        $this->items->each(function ($item) {
            $item->delete = true;
        });
    }

    /**
     * Remove all conditions from items in cart.
     *
     * @return void
     */
    public function clearItemConditions()
    {
        $this->items->each(function ($item) {
            $item->conditions->each(function ($condition) {
                $condition->delete = true;
            });
        });
    }

    /**
     * Remove all cart conditions.
     *
     * @return void
     */
    public function clearCartConditions()
    {
        $this->conditions->each(function ($condition) {
            $condition->delete = true;
        });
    }
}

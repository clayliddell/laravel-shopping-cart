<?php

namespace clayliddell\ShoppingCart\Database\Models;

class Cart extends CartBase
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array
     */
    public static $rules = [
        'session'  => 'required|string',
        'instance' => 'required|string',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session',
        'instance',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array
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
        return $this->hasMany(CartCondition::class);
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
            $item->delete();
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
                $condition->delete();
            });
        });
    }

    /**
     * Remove all cart conditions.
     *
     * @return void
     */
    public function clearConditions()
    {
        $this->conditions->each(function ($condition) {
            $condition->delete();
        });
    }
}

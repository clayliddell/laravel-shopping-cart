<?php

namespace clayliddell\ShoppingCart;

class Cart extends CartBase
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array
     */
    public static $rules = [
        'session'    => 'required|string',
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
    public function items()
    {
        return $this->hasMany('Model\Item');
    }

    /**
     * Get the condition type associated with this condition.
     *
     * @return void
     */
    public function conditions()
    {
        return $this->hasMany('Models\CartCondition');
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

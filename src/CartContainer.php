<?php

namespace clayliddell\ShoppingCart;

use clayliddell\ShoppingCart\Collections\{
    CartItemCollection,
    CartConditionCollection
};

class CartContainer
{
    /**
     *  Container for storing shopping cart items.
     *
     * @var CartItemCollection
     */
    public $items;

    /**
     * Container for storing shopping cart conditions.
     *
     * @var CartConditionCollection
     */
    public $conditions;

    public function __construct($items = [], $conditions = [])
    {
        // Initialize items container.
        $this->items = CartItemCollection::make($items);
        // Initialize conditions container.
        $this->conditions = CartConditionCollection::make($conditions);
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
        $this->items = CartItemCollection::make();
    }

    /**
     * Remove all conditions from items in cart.
     *
     * @return void
     */
    public function clearItemConditions()
    {
        foreach ($this->items as $item) {
            foreach ($item->conditions as $condition) {
                $condition->delete();
            }
        }
    }

    /**
     * Remove all cart conditions.
     *
     * @return void
     */
    public function clearConditions()
    {
        $this->conditions = CartConditionCollection::make();
    }
}

<?php

namespace clayliddell\ShoppingCart\Traits\Cart;

use Illuminate\Events\Dispatcher;

use clayliddell\ShoppingCart\Database\Models\{
    Cart as CartContainer,
    Condition,
    Item,
};
use clayliddell\ShoppingCart\EventCodes;
use clayliddell\ShoppingCart\Exceptions\CartSaveException;

trait ContentManagementTrait
{
    use ConditionManagementTrait;
    use ItemManagementTrait {
        countItems as count;
    }

    /**
     * Cart container.
     */
    protected CartContainer $cart;

    /**
     * Event Dispatcher.
     */
    protected Dispatcher $events;

    /**
     * Store changes to shopping cart collection to storage.
     *
     * @throws CartSaveException
     *   If an error occurred while attempting to save the shopping cart.
     */
    public function save(): void
    {
        // Dispatch 'saving' event before proceeding; if HALT_EXECUTION code is
        // returned, prevent shopping cart from being saved.
        if ($this->events->dispatch('saving', $this->cart) !== EventCodes::HALT_EXECUTION) {
            try {
                $this->doSave();
            } catch (\Exception $original_exception) {
                $message = 'Failed to save shopping cart items to storage.';
                throw new CartSaveException($message, 0, $original_exception);
            }

            // Dispatch 'saved' event.
            $this->events->dispatch('saved', $this->cart);
        }
    }

    /**
     * Save all of the shopping cart's items and conditions.
     */
    protected function doSave(): void
    {
        // Create a transaction for the shopping cart connection so that all
        // items and conditions will be updated on a successful save attempt, or
        // none will be saved on a failure of even one.
        $this->cart->getConnection()->transaction(function () {
            // Save cart model.
            $this->cart->save();
            // Save cart conditions.
            $this->cart->conditions->each(function ($condition) {
                // Ensure the condition has the right cart ID set.
                $condition->cart_id = $this->cart->id;
                $this->doSaveCartCondition($condition);
            });
            // Save cart items.
            $this->cart->items->each(fn ($item) => $this->doSaveCartItem($item));
        });
    }

    /**
     * Save the supplied shopping cart condition.
     *
     * @param Condition
     *   The condition to be saved.
     */
    protected function doSaveCartCondition(Condition $condition): void
    {
        // If the condition is flagged to be deleted, delete it.
        if ($condition->delete) {
            $condition->delete();
        // Otherwise save it.
        } else {
            $condition->save();
        }
    }

    /**
     * Save the supplied shopping cart item.
     *
     * @param Item
     *   The item to be saved.
     */
    protected function doSaveCartItem(Item $item): void
    {
        // If the item is flagged to be deleted, delete it.
        if ($item->delete) {
            $item->attributes->delete();
            $item->conditions->each->delete();
            $item->delete();
        // Otherwise ensure it has the right cart ID set for it.
        } else {
            $item->cart_id = $this->cart->id;
            // If the item has attributes, save associated
            // attributes.
            if (isset($item->attributes)) {
                $item->attributes->save();
                $item->attributes_id = $item->attributes->id;
            }
            $item->save();
            // Save item conditions.
            $item->conditions->each(function ($condition) use ($item) {
                // Ensure the condition has the right item ID set.
                $condition->item_id = $item->id;
                $this->doSaveCartCondition($condition);
            });
        }
    }

    /**
     * Remove all items and conditions from cart.
     */
    public function clear(): void
    {
        $this->cart->clear();
    }

    /**
     * Remove all items from cart.
     */
    public function clearItems(): void
    {
        $this->cart->clearItems();
    }

    /**
     * Remove all conditions from items in cart.
     */
    public function clearItemConditions(): void
    {
        $this->cart->clearItemConditions();
    }

    /**
     * Remove all cart conditions.
     */
    public function clearCartConditions(): void
    {
        $this->cart->clearCartConditions();
    }
}

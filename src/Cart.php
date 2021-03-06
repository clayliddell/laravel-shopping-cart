<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Events\Dispatcher;

use clayliddell\ShoppingCart\Database\Models\{
    Cart as CartContainer,
    Condition,
    Item,
};
use clayliddell\ShoppingCart\Exceptions\CartSaveException;
use clayliddell\ShoppingCart\Traits\Cart\{
    ConditionManagementTrait,
    ItemManagementTrait,
    PriceTrait,
};

/**
 * Shopping cart implementation.
 */
class Cart implements Arrayable
{
    use ConditionManagementTrait;
    use ItemManagementTrait {
        countItems as count;
    }
    use PriceTrait;

    /**
     * Whether to save the cart automatically on destruct.
     */
    protected bool $saveOnDestruct;

    /**
     * Event Dispatcher.
     */
    protected Dispatcher $events;

    /**
     * Cart container.
     */
    protected CartContainer $cart;

    /**
     * Creates a Cart object.
     *
     * @param string $instance
     *   Cart instance name.
     * @param string $session
     *   Cart session name.
     * @param Dispatcher $events
     *   Event dispatcher.
     * @param bool $save_on_destruct
     *   Whether to save items in cart upon destruct.
     */
    public function __construct(
        string $instance,
        string $session,
        Dispatcher $events,
        bool $save_on_destruct = true
    ) {
        // Initialize cart object.
        $this->events = $events;
        $this->saveOnDestruct = $save_on_destruct;
        $this->cart = CartContainer::firstOrNew([
            'instance' => $instance,
            'session'  => $session
        ]);
        // Dispatch 'constructed' event.
        $this->fireEvent('constructed', $this);
    }

    /**
     * Destroys a Cart object.
     */
    public function __destruct()
    {
        // If `$saveOnDestruct` flag is set to `true`, save the cart.
        if ($this->saveOnDestruct) {
            $this->cart->save();
        }
    }

    /**
     * Retrieve new cart instance with supplied instance and session names.
     *
     * @param string $instance
     *   New cart instance name.
     * @param string|null $session
     *   New cart session name.
     *
     * @return Cart
     *   New cart instance.
     */
    public function instance(string $instance, string $session = null): Cart
    {
        return new self(
            $instance,
            $session ?? $this->getSession(),
            $this->events,
            $this->saveOnDestruct
        );
    }

    /**
     * Get shopping cart session name.
     */
    public function getSession(): string
    {
        return $this->cart->session;
    }

    /**
     * Get shopping cart instance name.
     */
    public function getInstance(): string
    {
        return $this->cart->instance;
    }

    /**
     * Get all shopping cart sessions.
     */
    public static function getSessions(): array
    {
        return CartContainer::all()->pluck('session');
    }

    /**
     * Get all instances associated with a cart session.
     *
     * @param string $session
     *   The cart session to search by.
     *
     * @return array<string>
     *   Instances for cart's belonging to the supplied session.
     */
    public static function getInstances(string $session): array
    {
        return CartContainer::where('session', $session)->pluck('instance');
    }

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
        if ($this->fireEvent('saving', $this->cart) !== EventCodes::HALT_EXECUTION) {
            try {
                $this->doSave();
            } catch (\Exception $original_exception) {
                $message = 'Failed to save shopping cart items to storage.';
                throw new CartSaveException($message, 0, $original_exception);
            }

            // Dispatch 'saved' event.
            $this->fireEvent('saved', $this->cart);
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
        $this->clearHelper(__FUNCTION__, EventCodes::CLEARING_CART);
    }

    /**
     * Remove all items from cart.
     */
    public function clearItems(): void
    {
        $this->clearHelper(__FUNCTION__, EventCodes::CLEARING_ITEMS);
    }

    /**
     * Remove all conditions from items in cart.
     */
    public function clearItemConditions(): void
    {
        $this->clearHelper(__FUNCTION__, EventCodes::CLEARING_ITEM_CONDITIONS);
    }

    /**
     * Remove all cart conditions.
     */
    public function clearCartConditions(): void
    {
        $this->clearHelper(__FUNCTION__, EventCodes::CLEARING_CART_CONDITIONS);
    }

    /**
     * Handle all shopping cart clearing methods.
     *
     * @param string $clear_method
     *   CartContainer class clear method to be used.
     * @param int $clear_code
     *   Clear code used to signify what is being cleared from cart.
     */
    protected function clearHelper(string $clear_method, int $clear_code): void
    {
        // Dispatch 'clearing' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('clearing', $this->cart, $clear_code) !== EventCodes::HALT_EXECUTION) {
            // Remove all items from cart.
            $this->cart->{$clear_method}();
            // Dispatch 'cleared' event.
            $this->fireEvent('cleared', $this->cart, $clear_code);
        }
    }

    /**
     * Handle triggered events using Dispatcher provided.
     *
     * @param string $event
     *   Name of event being dispatched.
     * @param array $payload
     *   Optional values to be dispatched with the event.
     *
     * @return array|null
     *   Values returned from event listeners.
     */
    protected function fireEvent(string $event, ...$payload): ?array
    {
        return $this->events->dispatch($event, $payload);
    }

    /**
     * Convert cart items and conditions into an array.
     */
    public function toArray(): array
    {
        return [
            'items' => $this->cart->items->toArray(),
            'conditions' => $this->cart->conditions->toArray(),
        ];
    }

    /**
     * Pass requests for properties to underlying cart container.
     *
     * @param mixed $prop
     *   Property being requested.
     *
     * @return mixed
     *   Value of cart property.
     */
    public function __get($prop)
    {
        return $this->cart->$prop;
    }
}

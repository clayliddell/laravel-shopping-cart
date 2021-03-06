<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Events\Dispatcher;

use clayliddell\ShoppingCart\Database\Models\Cart as CartContainer;
use clayliddell\ShoppingCart\Traits\Cart\{
    ContentManagementTrait,
    PriceTrait,
};

/**
 * Shopping cart implementation.
 */
class Cart implements Arrayable
{
    use ContentManagementTrait;
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
        bool $save_on_destruct
    ) {
        // Initialize cart object.
        $this->events = $events;
        $this->saveOnDestruct = $save_on_destruct;
        $this->cart = CartContainer::firstOrNew([
            'instance' => $instance,
            'session'  => $session,
        ]);
        // Dispatch 'constructed' event.
        $this->events->dispatch('constructed', $this);
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
        return $this->cart->{$prop};
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
}

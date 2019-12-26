<?php

namespace clayliddell\ShoppingCart;

use clayliddell\ShoppingCart\Models\CartItem;
use Illuminate\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use clayliddell\ShoppingCart\config\EventCodes;
use clayliddell\ShoppingCart\Exceptions\{
    CartItemValidationException,
    CartSaveException,
};

/**
 * Shopping cart implementation.
 */
class Cart implements ArrayAccess, Arrayable
{
    /**
     * Event Dispatcher.
     *
     * @var Dispatcher
     */
    protected $events;

    /**
     * Cart instance name.
     *
     * @var string
     */
    protected $instanceName;

    /**
     * Cart session key.
     *
     * @var string
     */
    protected $sessionKey;

    /**
     * Config for shopping cart.
     *
     * @var array
     */
    protected $config;

    /**
     * DB connection name.
     *
     * @var string
     */
    protected $connection;

    /**
     * Cart container.
     *
     * @var CartCollection
     */
    protected $cart;


    /**
     * Removed cart item ids.
     *
     * @var array<string>
     */
    protected $removed = [];

    /**
     * @inheritDoc
     *
     * @param Dispatcher $events
     * @param string $instance_name
     * @param string $session_key
     * @param string $connection DB connection name.
     * @param array $config
     */
    public function __construct(
        Dispatcher $events,
        string $instance_name,
        string $session_key,
        string $connection,
        array $config
    ) {
        $this->events = $events;
        $this->instanceName = $instance_name;
        $this->sessionKey = $session_key;
        $this->connection = $connection;
        $this->config = $config;
        $this->cart = $this->getCartContent();
        $this->fireEvent('constructed', $this);
    }

    /**
     * Determine if an item exists at a given offset.
     *
     * @param  string $key
     * @return bool
     */
    public function offsetExists(string $key): bool
    {
        return isset($this->cart[$key]);
    }

    /**
     * Delete the item at a given offset.
     *
     * @param  string  $key Item key.
     * @return void
     */
    public function offsetUnset(string $key): void
    {
        $this->remove($key);
    }

    /**
     * Get the item at a given offset.
     *
     * @param  string $key Item key.
     * @return CartItem|null
     */
    public function offsetGet(string $key): ?CartItem
    {
        return $this->cart[$key] ?? null;
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->cart->toArray();
    }

    /**
     * Check whether shopping cart has item with provided item id(s).
     *
     * @param array|string $ids Shopping cart item id(s).
     * @return bool
     */
    public function has($ids): bool
    {
        return $this->cart->has($ids);
    }

    /**
     * Get shopping cart item with provided item id.
     *
     * @param string $id Shopping cart item id.
     * @return CartItem
     */
    public function get(string $id): CartItem
    {
        return $this->cart->get($id);
    }

    /**
     * Create shopping cart item and add it to shopping cart storage.
     *
     * @param string $id Item id.
     * @param string $name Item name.
     * @param float $price Item price.
     * @param int $quantity Item quantity.
     * @return CartItem Newly created item.
     */
    public function add(string $id, string $name, float $price, int $quantity): CartItem
    {
        $item_details = [
            'session_id' => $this->getSessionKey(),
            'item_id'    => $id,
            'name'       => $name,
            'price'      => $price,
            'quantity'   => $quantity,
        ];
        // Validate shopping cart item properties.
        $this->validate($item_details);
        // Create cart item.
        $item = $this->createItem($id, $name, $price, $quantity);
        // Dispatch 'adding' event before proceeding; if HALT_EXECUTION code is
        // returned, prevent shopping cart item from being added to cart.
        if ($this->fireEvent('adding', $item) !== EventCodes::HALT_EXECUTION) {
            // Add item to cart container.
            $this->cart[$id] = $item;
        }
        // Return newly created item.
        return $item;
    }

    /**
     * Remove shopping cart item(s) from the cart container.
     *
     * @param string $keys Key(s) of shopping cart item(s) to be removed.
     * @return void
     */
    public function remove(string $keys): void
    {
        // Dispatch 'removing' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('removing', $this->cart, $keys) === EventCodes::HALT_EXECUTION) {
            return;
        }
        // Record that the current item has been removed.
        $this->removed = array_merge($this->removed, (array) $keys);
        // Remove the item from the container.
        $this->cart->forget($keys, $this->getSessionKey());
        // Dispatch 'removed' event.
        $this->fireEvent('removed', $this->cart, $keys);
    }

    public function clear(): void
    {
        // Dispatch 'clearing' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('clearing', $this->cart) === EventCodes::HALT_EXECUTION) {
            return;
        }
        $this->cart = CartCollection::make();
        // Dispatch 'cleared' event.
        $this->fireEvent('cleared', $this->cart);
    }

    /**
     * Retrieve stored shopping cart content.
     *
     * @return Cart
     */
    public function getCartContent(): CartCollection
    {
        // Ensure that the cart has been initialized, and is a CartCollection.
        if (!$this->cart instanceof CartCollection) {
            // If the cart has yet to be initialized; initialize cart as a
            // CartCollection.
            $this->cart = CartCollection::make(
                CartItem::where('session_key', $this->getSessionKey())->get()
            );
        }
        // Return cart collection.
        return $this->cart;
    }

    /**
     * Get shopping cart instance name.
     *
     * @return string
     */
    protected function getInstanceName(): string
    {
        return $this->instanceName;
    }

    /**
     * Get shopping cart session key.
     *
     * @return string
     */
    protected function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * Create shopping cart item and add it to local shopping cart container.
     *
     * @param string $id Item id.
     * @param string $name Item name.
     * @param float $price Item price.
     * @param int $quantity Item quantity.
     * @return CartItem Newly created item.
     */
    protected function createItem(
        string $id,
        string $name,
        float $price,
        int $quantity,
        ?string $session_key = null
    ): CartItem {
        // Create an item using the provided details.
        $item = CartItem::create([
            $session_key ?? $this->getSessionKey(),
            $id,
            $name,
            $price,
            $quantity
        ]);
        // Return newly created cart item.
        return $item;
    }

    /**
     * Validate item properties.
     *
     * @param array $item Item properies.
     * @return void
     * @throws CartItemValidationException
     */
    protected function validate(array $item): void
    {
        // Validate shopping cart item properties.
        $validator = Validator::make($item, CartItem::$rules);
        // Alert user if validation fails.
        if ($validator->fails()) {
            throw new CartItemValidationException($validator->messages()->first());
        }
        // Dispatch 'validating' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('validating', $this->cart) === EventCodes::HALT_EXECUTION) {
            return;
        }
    }

    /**
     * Store changes to shopping cart collection to storage.
     *
     * @return void
     * @throws Throwable
     */
    protected function save(): void
    {
        // Dispatch 'saving' event before proceeding; if HALT_EXECUTION code is
        // returned, prevent shopping cart from being saved.
        if ($this->fireEvent('saving', $this->cart) === EventCodes::HALT_EXECUTION) {
            return;
        }
        try {
            // Create a transaction for the shopping cart connection so that all
            // items will be updated on a successful save attempt, or none will
            // be saved on a failure of even one.
            CartItem::resolveConnection($this->connection)->transaction(function () {
                // Save each item in the shopping cart.
                $this->cart->each(function ($item) {
                    $item->save();
                });
                // Delete items which have been removed from shopping cart.
                CartItem::where('session_key', $this->getSessionKey())
                ->whereIn('item_id', (array) $this->removed)
                ->delete();
            });
        } catch (Exception $original_exception) {
            $message = 'Failed to save shopping cart items to storage.';
            throw new CartSaveException($message, 0, $original_exception);
        }
        // Dispatch 'saved' event.
        $this->fireEvent('saved', $this->cart);
    }

    /**
     * Handle triggered events using Dispatcher provided.
     *
     * @param string $event Name of event being dispatched.
     * @param array $payload Optional values to be dispatched with the event.
     * @return array|null Values returned from event listeners.
     */
    protected function fireEvent(string $eventName, ...$payload): ?array
    {
        return $this->events->dispatch(
            $this->getInstanceName() . '.' . $eventName,
            $payload
        );
    }
}

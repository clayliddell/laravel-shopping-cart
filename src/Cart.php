<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use clayliddell\ShoppingCart\Validation\CartValidator;
use clayliddell\ShoppingCart\Models\{
    Item,
    CartCondition,
    Cart as CartContainer,
};
use clayliddell\ShoppingCart\Exceptions\{
    ItemValidationException,
    CartConditionValidationException,
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
     * Cart session.
     *
     * @var string
     */
    protected $session;

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
     * @var CartContainer
     */
    protected $cart;

    /**
     * @inheritDoc
     *
     * @param Dispatcher $events        Event dispatcher.
     * @param string     $instance_name Cart instance name.
     * @param string     $session       Session ID.
     * @param string     $connection    DB connection name.
     * @param array      $config        Cart config.
     */
    public function __construct(
        Dispatcher $events,
        string $instance_name,
        string $session,
        string $connection,
        array $config
    ) {
        // Initialize cart object.
        $this->events = $events;
        $this->instanceName = $instance_name;
        $this->session = $session;
        $this->connection = $connection;
        $this->config = $config;
        $this->cart = $this->getCartContent();
        // Dispatch 'constructed' event.
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
        return isset($this->cart->items[$key]);
    }

    /**
     * Delete the item at a given offset.
     *
     * @param  string  $key Item key.
     * @return void
     */
    public function offsetUnset(string $key): void
    {
        $this->removeItem($key);
    }

    /**
     * Get the item at a given offset.
     *
     * @param  string $key Item key.
     * @return Item|null
     */
    public function offsetGet(string $key): ?Item
    {
        return $this->cart->items[$key] ?? null;
    }

    /**
     * Convert cart items and conditions to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->cart->toArray();
    }

    /**
     * Retrieve stored shopping cart content.
     *
     * @return Cart
     */
    public function getCartContent(): CartContainer
    {
        // Ensure that the cart has been initialized, and is a CartContainer.
        if (!$this->cart instanceof CartContainer) {
            // If the cart has yet to be initialized; initialize cart as a
            // CartContainer with the cart items associated with the current
            // session.
            $this->cart = CartContainer::where('session_id', $this->getSession())->get();
        }
        // Return cart collection.
        return $this->cart;
    }

    /**
     * Check whether shopping cart has item with provided item id(s).
     *
     * @param  string[] $ids Shopping cart item id(s).
     * @return bool
     */
    public function has(string ...$ids): bool
    {
        return $this->cart->items->has(...$ids);
    }

    /**
     * Get shopping cart item with provided item id.
     *
     * @param  string $id Shopping cart item id.
     * @return Item
     */
    public function get(string $id): Item
    {
        return $this->cart->items->get($id);
    }

    /**
     * Check whether shopping cart has condition with provided condition
     * name(s).
     *
     * @param  string[] $name Shopping cart condition name(s).
     * @return bool
     */
    public function hasCondition(string ...$names): bool
    {
        return $this->cart->conditions->has(...$names);
    }

    /**
     * Get shopping cart condition with provided condition name.
     *
     * @param  string $name Shopping cart condition name.
     * @return CartCondition
     */
    public function getCondition(string $name): CartCondition
    {
        return $this->cart->conditions->get($name);
    }

    /**
     * Alias for `addItem` method.
     *
     * @param  string  $id
     * @param  string  $name
     * @param  float   $price
     * @param  integer $quantity
     * @return Item
     */
    public function add(string $id, string $name, float $price, int $quantity): Item
    {
        return $this->addItem($id, $name, $price, $quantity);
    }

    /**
     * Create shopping cart item and add it to shopping cart storage.
     *
     * @param  string $id Item id.
     * @param  string $name Item name.
     * @param  float  $price Item price.
     * @param  int    $quantity Item quantity.
     * @return Item Newly created item.
     */
    public function addItem(string $id, string $name, float $price, int $quantity): Item
    {
        $item_details = [
            'session_id' => $this->getSession(),
            'item_id'    => $id,
            'name'       => $name,
            'price'      => $price,
            'quantity'   => $quantity,
        ];
        // Validate shopping cart item properties.
        $this->validateItem($item_details);
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
     * Create shopping cart condition and add it to shopping cart storage.
     *
     * @param string $name   Condition name.
     * @param string $type   Condition type.
     * @param float  $value  Condition value.
     * @return CartCondition Newly created condition.
     */
    public function addCondition(string $name, string $type, float $value): CartCondition
    {
        $condition_details = [
            'name' => $name,
            'type' => $type,
            'value' => $value,
        ];
        // Validate shopping cart item properties.
        $this->validateCondition($condition_details);
        // Create cart item.
        $condition = $this->createCondition($name, $type, $value);
        // Dispatch 'adding' event before proceeding; if HALT_EXECUTION code is
        // returned, prevent shopping cart item from being added to cart.
        if ($this->fireEvent('adding', $condition) !== EventCodes::HALT_EXECUTION) {
            // Add item to cart container.
            $this->cart->conditions[$name] = $condition;
        }
        // Return newly created item.
        return $condition;
    }

    /**
     * Alias for `removeItem` method.
     *
     * @param string[] $keys
     * @return void
     */
    public function remove(string ...$keys): void
    {
        $this->removeItem(...$keys);
    }

    /**
     * Remove item(s) from the shopping cart container.
     *
     * @param string[] $keys Key(s) of shopping cart item(s) to be removed.
     * @return void
     */
    public function removeItem(string ...$keys): void
    {
        // Dispatch 'removing_items' event before proceeding; if
        // HALT_EXECUTION code is returned, prevent shopping cart from being
        // saved.
        if ($this->fireEvent('removing_items', $this->cart, $keys) === EventCodes::HALT_EXECUTION) {
            return;
        }
        // Record that the current item has been removed.
        $this->removed = array_merge($this->removed, $keys);
        // Remove the item from the container.
        $this->cart->items->forget($keys, $this->getSession());
        // Dispatch 'removed_items' event.
        $this->fireEvent('removed_items', $this->cart, $keys);
    }

    /**
     * Remove condition(s) from the shopping cart container.
     *
     * @param string[] $keys Key(s) of shopping cart conditions(s) to be removed.
     * @return void
     */
    public function removeCondition(string ...$keys): void
    {
        // Dispatch 'removing_conditions' event before proceeding; if
        // HALT_EXECUTION code is returned, prevent shopping cart from being
        // saved.
        if ($this->fireEvent('removing_conditions', $this->cart, $keys) === EventCodes::HALT_EXECUTION) {
            return;
        }
        // Record that the current item has been removed.
        $this->removed = array_merge($this->removed, $keys);
        // Remove the item from the container.
        $this->cart->conditions->forget($keys, $this->getSession());
        // Dispatch 'removed_conditions' event.
        $this->fireEvent('removed_conditions', $this->cart, $keys);
    }

    /**
     * Remove all items and conditions from cart.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->clearHelper(__FUNCTION__, EventCodes::CLEARING_CART);
    }

    /**
     * Remove all items from cart.
     *
     * @return void
     */
    public function clearItems(): void
    {
        $this->clearHelper(__FUNCTION__, EventCodes::CLEARING_ITEMS);
    }

    /**
     * Remove all conditions from items in cart.
     *
     * @return void
     */
    public function clearItemConditions(): void
    {
        $this->clearHelper(__FUNCTION__, EventCodes::CLEARING_ITEM_CONDITIONS);
    }

    /**
     * Remove all cart conditions.
     *
     * @return void
     */
    public function clearConditions(): void
    {
        $this->clearHelper(__FUNCTION__, EventCodes::CLEARING_CART_CONDITIONS);
    }

    /**
     * Handle all shopping cart clearing methods.
     *
     * @var    string $clear_method CartContainer class clear method to be used.
     * @var    int    $clear_code   Clear code used to signify what is being
     *                cleared from cart.
     * @return void
     */
    protected function clearHelper(string $clear_method, int $clear_code)
    {
        // Dispatch 'clearing' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('clearing', $this->cart, $clear_code) === EventCodes::HALT_EXECUTION) {
            return;
        }
        // Remove all items from cart.
        $this->cart->$clear_method();
        // Dispatch 'cleared' event.
        $this->fireEvent('cleared', $this->cart, $clear_code);
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
    protected function getSession(): string
    {
        return $this->session;
    }

    /**
     * Create shopping cart item and add it to cart container.
     *
     * @param  string $id Item id.
     * @param  string $name Item name.
     * @param  float  $price Item price.
     * @param  int    $quantity Item quantity.
     * @return Item   Newly created item.
     */
    protected function createItem(
        string $id,
        string $name,
        float $price,
        int $quantity,
        ?string $session = null
    ): Item {
        // Create an item using the provided details.
        $item = Item::create([
            $session ?? $this->getSession(),
            $id,
            $name,
            $price,
            $quantity
        ]);
        // Return newly created cart item.
        return $item;
    }

    /**
     * Create shopping cart condition and add it to cart container.
     *
     * @param  string $name  Condition name.
     * @param  string $type  Condition type.
     * @param  float  $value Condition value.
     * @return CartCondition Newly created condition.
     */
    protected function createCondition(
        string $name,
        string $type,
        float $value
    ): CartCondition {
        // Create a condition using the provided details.
        $condition = CartCondition::create([
            $name,
            $type,
            $value
        ]);
        // Return newly created cart condition.
        return $condition;
    }

    /**
     * Validate item properties.
     *
     * @param  array $item Item properies.
     * @return void
     * @throws ItemValidationException
     */
    protected function validateItem(array $item): void
    {
        // Validate shopping cart item properties.
        $validator = CartValidator::make($item, Item::$rules);
        // Alert user if validation fails.
        if ($validator->fails()) {
            throw new ItemValidationException($validator->messages()->first());
        }
        // Dispatch 'validating' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('validating', $this->cart, $item) === EventCodes::HALT_EXECUTION) {
            return;
        }
    }

    /**
     * Validate condition properties.
     *
     * @param  array $condition Condition properies.
     * @return void
     * @throws CartConditionValidationException
     */
    protected function validateCondition(array $condition): void
    {
        // Validate shopping cart condition properties.
        $validator = CartValidator::make($condition, CartCondition::$rules);
        // Alert user if validation fails.
        if ($validator->fails()) {
            throw new CartConditionValidationException($validator->messages()->first());
        }
        // Dispatch 'validating' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('validating', $this->cart, $condition) === EventCodes::HALT_EXECUTION) {
            return;
        }
    }

    /**
     * Store changes to shopping cart collection to storage.
     *
     * @return void
     * @throws CartSaveException
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
            // items and conditions will be updated on a successful save
            // attempt, or none will be saved on a failure of even one.
            $this->cart->saveOrFail();
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
     * @param  string $event Name of event being dispatched.
     * @param  array  $payload Optional values to be dispatched with the event.
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

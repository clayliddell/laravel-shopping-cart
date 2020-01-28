<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use clayliddell\ShoppingCart\Validation\CartValidator;
use clayliddell\ShoppingCart\Database\Models\{
    Item,
    CartCondition,
    Cart as CartContainer,
};
use clayliddell\ShoppingCart\Exceptions\{
    ItemValidationException,
    CartConditionValidationException,
    CartOffsetSetDisallowed,
    CartSaveException,
};

/**
 * Shopping cart implementation.
 */
class Cart implements \ArrayAccess, Arrayable
{
    /**
     * Cart instance name.
     *
     * @var string
     */
    protected string $instance;

    /**
     * Cart session.
     *
     * @var string
     */
    protected string $session;

    /**
     * Whether to save the cart automatically on destruct.
     *
     * @var bool
     */
    protected bool $saveOnDestruct;

    /**
     * Event Dispatcher.
     *
     * @var Dispatcher
     */
    protected Dispatcher $events;

    /**
     * Cart container.
     *
     * @var CartContainer
     */
    protected CartContainer $cart;

    /**
     * @inheritDoc
     *
     * @param string     $instance      Cart instance name.
     * @param string     $session       Session ID.
     * @param Dispatcher $events        Event dispatcher.
     */
    public function __construct(
        string $instance,
        string $session,
        Dispatcher $events,
        bool $save_on_destruct = true
    ) {
        // Initialize cart object.
        $this->instance = $instance;
        $this->session = $session;
        $this->events = $events;
        $this->saveOnDestruct = $save_on_destruct;
        $this->initializeCart();
        // Dispatch 'constructed' event.
        $this->fireEvent('constructed', $this);
    }

    /**
     * @inheritDoc
     */
    public function __destruct()
    {
        // If `$saveOnDestruct` flag is set to `true`, save the cart.
        if ($this->saveOnDestruct) {
            $this->cart->save();
        }
    }

    /**
     * Create a new cart instance with the provided details.
     *
     * @param  string          $instance Cart instance name.
     * @param  string          $session  Session ID.
     * @param  Dispatcher|null $events   Event dispatcher.
     * @return self
     */
    public function make(
        string $instance,
        string $session,
        Dispatcher $events = null
    ): self {
        return new static($instance, $session, $events ?? $this->events);
    }

    /**
     * Determine if an item exists at a given offset.
     *
     * @param  mixed $id Item id.
     * @return bool
     */
    public function offsetExists($id): bool
    {
        return isset($this->cart->items[$id]);
    }

    /**
     * Delete the item at a given offset.
     *
     * @param  mixed  $id Item id.
     * @return void
     */
    public function offsetUnset($id): void
    {
        $this->removeItem($id);
    }

    /**
     * Get the item at a given offset.
     *
     * @param  mixed $id Item id.
     * @return Item|null
     */
    public function offsetGet($id)
    {
        return $this->cart->items[$id] ?? null;
    }

    /**
     * Disallow usage of `ArrayAccess::offsetSet()` method.
     *
     * @param mixed $id
     * @param mixed $val
     * @return void
     */
    public function offsetSet($id, $val): void
    {
        throw new CartOffsetSetDisallowed("Usage of the ArrayAccess::offsetSet() is not allowed.");
    }

    /**
     * Convert cart items and conditions to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->cart->items->toArray();
    }

    /**
     * Get the current cart instance or change to the specified cart instance.
     *
     * @param  string      $instance
     * @param  bool        $preserve_items
     * @param  string|null $session
     * @return string|Cart
     */
    public function instance(
        string $instance = '',
        bool $preserve_items = false,
        string $session = null
    ) {
        // If an instance is provided, set the instance.
        if (!empty($instance)) {
            return $this->setInstance($instance, $preserve_items, $session);
        } else {
            return $this->getInstance();
        }
    }

    /**
     * Get all instances associated with a cart session.
     *
     * @param  string|null $session
     * @return array
     */
    public function instances(string $session = null): array
    {
        return CartContainer::where('session', $session ?? $this->getSession())
            ->pluck('instance');
    }

    /**
     * Retrieve stored shopping cart content.
     *
     * @return Cart
     */
    public function getCartContent(): CartContainer
    {
        // Ensure that the cart has been initialized, and is a CartContainer
        //before returning cart collection.
        return $this->cart instanceof CartContainer ? $this->cart : $this->initializeCart();
    }

    /**
     * Initialize instance of `CartContainer`.
     *
     * Create new instance of `CartContainer` if one does not already exist,
     * otherwise return existing instance.
     *
     * @param string $instance
     * @param string $session
     * @return CartContainer
     */
    protected function initializeCart(string $instance = null, string $session = null): CartContainer
    {
        // Retrieve instance and session for cart.
        $instance ??= $this->getInstance();
        $session  ??= $this->getSession();

        // Initialize cart as a CartContainer with the cart items associated
        // with the current session and same instance name provided (or create a
        // new CartContainer instance).
        return $this->cart = CartContainer::firstOrNew(compact('instance', 'session'));
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
     * @return Item|null
     */
    public function get(string $id): ?Item
    {
        return $this->cart->items->where('id', $id)->first();
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
     * @return CartCondition|null
     */
    public function getCondition(string $name): ?CartCondition
    {
        return $this->cart->conditions->where('name', $name)->first();
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
    public function addItem(string $item_id, string $name, float $price, int $quantity): Item
    {
        // Retrieve sesssion.
        $session = $this->getSession();
        // Validate shopping cart item properties.
        $this->validateItem(compact('session', 'item_id', 'name', 'price', 'quantity'));
        // Create cart item.
        $item = $this->createItem($item_id, $name, $price, $quantity);
        // Dispatch 'adding' event before proceeding; if HALT_EXECUTION code is
        // returned, prevent shopping cart item from being added to cart.
        if ($this->fireEvent('adding', $item) !== EventCodes::HALT_EXECUTION) {
            // Associate newly created item with cart items.
            $this->cart->items->associate($item);
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
     * @return ?CartCondition Newly created condition.
     */
    public function addCondition(string $name, string $type, float $value): ?CartCondition
    {
        // If shopping cart condition properties fail validation, prevent
        // condition from being added to cart.
        if ($this->validateCondition(compact('name', 'type', 'value'))) {
            return null;
        }
        // Create cart condition.
        $condition = $this->createCondition($name, $type, $value);
        // Dispatch 'adding' event before proceeding; if HALT_EXECUTION code is
        // returned, prevent shopping cart condition from being added to cart.
        if ($this->fireEvent('adding', $condition) !== EventCodes::HALT_EXECUTION) {
            // Associate newly created condition with cart.
            $this->cart->conditions->associate($condition);
        }
        // Return newly created condition.
        return $condition;
    }

    /**
     * Alias for `removeItem` method.
     *
     * @param string[] $ids
     * @return void
     */
    public function remove(string ...$ids): void
    {
        $this->removeItem(...$ids);
    }

    /**
     * Remove item(s) from the shopping cart container.
     *
     * @param string[] $ids Key(s) of shopping cart item(s) to be removed.
     * @return void
     */
    public function removeItem(string ...$ids): void
    {
        // Dispatch 'removing_items' event before proceeding; if
        // HALT_EXECUTION code is returned, prevent shopping cart from being
        // saved.
        if ($this->fireEvent('removing_items', $this->cart, $ids) === EventCodes::HALT_EXECUTION) {
            return;
        }
        // Delete the item(s) from cart (and database if stored).
        $this->cart->items->find($ids)->each(fn ($item) => $item->delete());
        // Dispatch 'removed_items' event.
        $this->fireEvent('removed_items', $this->cart, $ids);
    }

    /**
     * Remove condition(s) from the shopping cart container.
     *
     * @param string[] $ids Id(s) of shopping cart conditions(s) to be removed.
     * @return void
     */
    public function removeCondition(string ...$ids): void
    {
        // Dispatch 'removing_conditions' event before proceeding; if
        // HALT_EXECUTION code is returned, prevent shopping cart from being
        // saved.
        if ($this->fireEvent('removing_conditions', $this->cart, $ids) === EventCodes::HALT_EXECUTION) {
            return;
        }
        // Delete the condition(s) from cart (and database if stored).
        $this->cart->conditions->find($ids)->each(fn ($condition) => $condition->delete());
        // Dispatch 'removed_conditions' event.
        $this->fireEvent('removed_conditions', $this->cart, $ids);
    }

    /**
     * Get the number of items in cart.
     *
     * @return integer
     */
    public function count(): int
    {
        return $this->items->count();
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
    protected function getInstance(): string
    {
        return $this->instance;
    }

    /**
     * Switch cart to specified instance.
     *
     * Preserve items from current cart into new one if specified to do so.
     *
     * @param string      $instance New cart instance name.
     * @param boolean     $preserve_items Whether to preserve items from current
     *                    cart instance.
     * @param string|null $session New cart instance session.
     * @return Cart
     */
    protected function setInstance(
        string $instance,
        bool $preserve_items = false,
        string $session = null
    ): Cart {
        $old_cart = null;
        // If preserve items flag is set, store old cart items for reference.
        if ($preserve_items) {
            $old_cart = $this->cart;
        }

        // Retrieve instance of CartContainer with provided details; or
        // initialize a new cart instance.
        $this->initializeCart($instance, $session);

        // Copy/re-associate old cart items and conditions to new cart.
        if (isset($old_cart)) {
            foreach (['items', 'conditions'] as $details) {
                $old_cart->$details->each(
                    fn ($detail) => $this->cart->$details->associate($detail)
                );
            }
        }

        // Return instance of Cart class.
        return $this;
    }

    /**
     * Get shopping cart session.
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
     * @return bool Whether the condition passed validation.
     * @throws CartConditionValidationException
     */
    protected function validateCondition(array $condition): bool
    {
        // Initialize `$passed` used to store whether the condition passed
        // validated.
        $passed = true;
        // Validate shopping cart condition properties.
        $validator = CartValidator::make($condition, CartCondition::$rules);
        // Alert user if validation fails.
        if ($validator->fails()) {
            if (!config('shopping_cart.ignore_condition_validation', false)) {
                throw new CartConditionValidationException($validator->messages()->first());
            }
            $passed = false;
        }
        // Dispatch 'validating' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('validating', $this->cart, $condition) === EventCodes::HALT_EXECUTION) {
            $passed = false;
        }
        // Return whether the condition passed validation.
        return $passed;
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
            $this->getInstance() . '.' . $eventName,
            $payload
        );
    }
}

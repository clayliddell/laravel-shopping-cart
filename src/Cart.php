<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Events\Dispatcher;
use clayliddell\ShoppingCart\Database\Models\{
    Item,
    Condition,
    ConditionType,
    Cart as CartContainer,
};
use clayliddell\ShoppingCart\Exceptions\{
    ItemValidationException,
    CartOffsetSetDisallowed,
    CartSaveException,
};
use clayliddell\ShoppingCart\Validation\CartValidator;
use clayliddell\ShoppingCart\Traits\PriceTrait;

/**
 * Shopping cart implementation.
 */
class Cart implements \ArrayAccess, Arrayable
{
    use PriceTrait;

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
     * @param string $instance
     *   Cart instance name.
     * @param string $session
     *   Session ID.
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
     * @param string $instance
     *   Cart instance name.
     * @param string $session
     *   Session ID.
     * @param Dispatcher|null $events
     *   Event dispatcher.
     *
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
     * @param mixed $id
     *   Item id.
     *
     * @return bool
     */
    public function offsetExists($id): bool
    {
        return isset($this->cart->items[$id]);
    }

    /**
     * Delete the item at a given offset.
     *
     * @param mixed  $id
     *   Item id.
     *
     * @return void
     */
    public function offsetUnset($id): void
    {
        $this->removeItem($id);
    }

    /**
     * Get the item at a given offset.
     *
     * @param mixed $id
     *   Item id.
     *
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
     *
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
        return [
            'items' => $this->cart->items->toArray(),
            'conditions' => $this->cart->conditions->toArray(),
        ];
    }

    /**
     * Get the current cart instance or change to the specified cart instance.
     *
     * @param string $instance
     * @param bool $preserve_items
     * @param string|null $session
     *
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
     * @param string|null $session
     *
     * @return array
     */
    public function instances(string $session = null): array
    {
        $session ??= $this->getSession();
        return CartContainer::where('session', $session)->pluck('instance');
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
     *
     * @return CartContainer
     */
    protected function initializeCart(string $instance = null, string $session = null): CartContainer
    {
        // Initialize cart as a CartContainer with the cart items associated
        // with the current session and same instance name provided (or create a
        // new CartContainer instance).
        return $this->cart = CartContainer::firstOrNew([
            'instance' => $instance ?? $this->getInstance(),
            'session'  => $session  ?? $this->getSession()
        ]);
    }

    /**
     * Check whether shopping cart has item with provided item id(s).
     *
     * @param array<string> $ids
     *   Shopping cart item id(s).
     *
     * @return bool
     */
    public function has(string ...$ids): bool
    {
        return $this->cart->items->has(...$ids);
    }

    /**
     * Get shopping cart item with provided item id.
     *
     * @param string $id
     *   Shopping cart item id.
     *
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
     * @param array<string> $name
     *   Shopping cart condition name(s).
     *
     * @return bool
     */
    public function hasCondition(string ...$names): bool
    {
        return $this->cart->conditions->has(...$names);
    }

    /**
     * Get shopping cart condition with provided condition name.
     *
     * @param string $name
     *   Shopping cart condition name.
     *
     * @return Condition|null
     */
    public function getCondition(string $name): ?Condition
    {
        return $this->cart->conditions->where('name', $name)->first();
    }

    /**
     * Alias for `addItem` method.
     *
     * @param int $sku_id
     * @param integer $quantity
     * @param array $attr
     *
     * @return Item
     */
    public function add(int $sku_id, int $quantity, ?array $attr): Item
    {
        return $this->addItem($sku_id, $quantity, $attr);
    }

    /**
     * Create shopping cart item and add it to shopping cart storage.
     *
     * @param int $sku_id
     *   Item sku.
     * @param int $quantity
     *   Item quantity.
     * @param array $attr
     *   Additional item attributes.
     *
     * @return Item
     *   Newly created item.
     */
    public function addItem(int $sku_id, int $quantity, ?array $attributes): Item
    {
        // Validate shopping cart item properties.
        $this->validateItem([
            'session'    => $this->getSession(),
            'cart_id'    => $this->cart->id,
            'sku_id'     => $sku_id,
            'quantity'   => $quantity,
            'attributes' => $attributes,
        ]);
        if (isset($attributes)) {
            $this->validateItemAttributes($attributes);
        }
        // Create cart item.
        $item = $this->createItem($sku_id, $quantity, $attributes);
        // Dispatch 'adding' event before proceeding; if HALT_EXECUTION code is
        // returned, prevent shopping cart item from being added to cart.
        if ($this->fireEvent('adding_item', $item) !== EventCodes::HALT_EXECUTION) {
            // Associate the newly created item with the cart.
            $item->cart()->associate($this->cart);
            $this->cart->items->add($item);
        }
        // Return newly created item.
        return $item;
    }

    /**
     * Attempt to apply shopping cart condition of the suplied type to cart.
     *
     * @param ConditionType $condition_type
     *   Condition type being applied to the cart.
     * @param bool $validate
     *   Whether to validate the supplied cart condition before application.
     *
     * @return Condition|null
     *   Condition which was applied or false on fail.
     */
    public function addCondition(ConditionType $condition_type, bool $validate = true): ?Condition
    {
        // Ensure procedure was not halted and condition passed validation.
        if (!$validate || $condition_type->validate($this->cart)) {
            // Create condition of type for cart.
            $condition = Condition::make([
                'cart_id' => $this->cart->id,
                'type_id' => $condition_type->id,
            ]);
            // Dispatch 'adding condition' event and check result.
            if ($this->fireEvent('adding_cart_condition', $condition) !== EventCodes::HALT_EXECUTION) {
                // Associate condition with cart.
                $this->cart->conditions->add($condition);
            }
        } else {
            $condition = null;
        }
        // Return condition.
        return $condition;
    }

    /**
     * Process items in cart and add appropriate conditions.
     *
     * @return array<Condition>
     */
    public function applyConditions(): array
    {
        $conditions = [];
        // Iterate over all cart conditions, checking whether they should be
        // applied to the cart or its items.
        ConditionType::all()->each(function ($condition_type) use (&$conditions) {
            // Attempt to add condition to the cart.
            $conditions[] = $this->addCondition($condition_type);
            // Attempt to add condition to each item in cart.
            $conditions = array_merge(
                $conditions,
                $this->items->map(fn ($item) => $item->addCondition($condition_type))->all(),
            );
        });

        return $conditions;
    }

    /**
     * Alias for `removeItem()` method.
     *
     * @param array<int> $ids
     *
     * @return void
     */
    public function remove(int ...$ids): void
    {
        $this->removeItem(...$ids);
    }

    /**
     * Remove item(s) from the shopping cart container.
     *
     * @param array<int> $ids
     *   Key(s) of shopping cart item(s) to be removed.
     *
     * @return void
     */
    public function removeItem(int ...$ids): void
    {
        // Dispatch 'removing_items' event before proceeding; if
        // HALT_EXECUTION code is returned, prevent shopping cart from being
        // saved.
        if ($this->fireEvent('removing_items', $this->cart, $ids) !== EventCodes::HALT_EXECUTION) {
            // Flag specified items for deletion.
            $this->cart->items->find($ids)->each(fn ($item) => $item->delete = true);
            // Dispatch 'removed_items' event.
            $this->fireEvent('removed_items', $this->cart, $ids);
        }
    }

    /**
     * Remove condition(s) from the shopping cart container.
     *
     * @param array<string> $ids
     *   Id(s) of shopping cart conditions(s) to be removed.
     *
     * @return void
     */
    public function removeCondition(string ...$ids): void
    {
        // Dispatch 'removing_conditions' event before proceeding; if
        // HALT_EXECUTION code is returned, prevent shopping cart from being
        // saved.
        if ($this->fireEvent('removing_conditions', $this->cart, $ids) !== EventCodes::HALT_EXECUTION) {
            // Delete the condition(s) from cart (and database if stored).
            $this->cart->conditions->find($ids)->each(fn ($condition) => $condition->delete());
            // Dispatch 'removed_conditions' event.
            $this->fireEvent('removed_conditions', $this->cart, $ids);
        }
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
     *
     * @return void
     */
    protected function clearHelper(string $clear_method, int $clear_code)
    {
        // Dispatch 'clearing' event before proceeding; if HALT_EXECUTION code
        // is returned, prevent shopping cart from being saved.
        if ($this->fireEvent('clearing', $this->cart, $clear_code) !== EventCodes::HALT_EXECUTION) {
            // Remove all items from cart.
            $this->cart->$clear_method();
            // Dispatch 'cleared' event.
            $this->fireEvent('cleared', $this->cart, $clear_code);
        }
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
     * @param string $instance
     *   New cart instance name.
     * @param boolean $preserve_items
     *   Whether to preserve items from current cart instance.
     * @param string|null $session
     *   New cart instance session.
     *
     * @return Cart
     */
    protected function setInstance(
        string $instance,
        bool $preserve_items = false,
        string $session = null
    ): Cart {
        // Retrieve new cart with supplied instance and session.
        $new_cart = new self(
            $instance,
            $session ?? $this->session,
            $this->events,
            $this->saveOnDestruct
        );

        // Copy/re-associate old cart items and conditions to new cart.
        if ($preserve_items) {
            foreach (['items', 'conditions'] as $details) {
                $this->cart->$details->each(
                    fn ($detail) => $new_cart->$details->add($detail)
                );
            }
        }

        // Return new Cart instance.
        return $new_cart;
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
     * @param int $sku_id
     *   Item sku id,
     * @param int $quantity
     *   Item quantity.
     * @param array|null $attr
     *   Item attributes.
     * @param string|null $session
     *   Session id.
     *
     * @return Item
     *   Newly created item.
     */
    protected function createItem(
        int $sku_id,
        int $quantity,
        ?array $attr,
        ?string $session = null
    ): Item {
        // Retrieve fully qualified path to item attributes model.
        $item_attr_model = config('shopping_cart.cart_item_attributes_model', '\App\ItemAttributes');
        // Make and eager load item attributes model.
        $attributes = $attr ? $item_attr_model::make($attr)->load() : null;
        // Create an item using the provided details.
        $item = Item::make([
            'cart_id'       => $this->cart->id,
            'session'       => $session ?? $this->getSession(),
            'sku_id'        => $sku_id,
            'quantity'      => $quantity,
        ])->load();
        // Associate attributes with item.
        if (isset($attributes)) {
            $item->attributes()->associate($attributes);
        }

        return $item;
    }

    /**
     * Validate item properties.
     *
     * @param array $item
     *   Item properies.
     *
     * @return void
     *
     * @throws ItemValidationException
     */
    protected function validateItem(array $item): void
    {
        // Validate shopping cart item properties.
        $validator = CartValidator::make($item, Item::rules());
        // Alert user if validation fails.
        if ($validator->fails()) {
            throw new ItemValidationException($validator->messages()->first());
        }
        if (isset($item['attributes'])) {
            $this->validateItemAttributes($item['attributes']);
        }
        // Dispatch 'validating_item' event to allow for custom item validation.
        $this->fireEvent('validating_item', $this->cart, $item);
    }

    /**
     * Validate item attributes properties.
     *
     * @param array $attr
     *   Item attributes properies.
     *
     * @return void
     *
     * @throws ItemValidationException
     */
    protected function validateItemAttributes(array $attr): void
    {
        // Retrieve fully qualified path to item attributes model.
        $item_attr_model = config('shopping_cart.cart_item_attributes_model', '\App\ItemAttributes');
        // Validate shopping cart item properties.
        $validator = CartValidator::make($attr, $item_attr_model::rules());
        // Alert user if validation fails.
        if ($validator->fails()) {
            throw new ItemValidationException($validator->messages()->first());
        }
    }

    /**
     * Store changes to shopping cart collection to storage.
     *
     * @return void
     *
     * @throws CartSaveException
     */
    public function save(): void
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
            $this->cart->getConnection()->transaction(function () {
                // Save cart model.
                $this->cart->save();
                // Save cart conditions.
                $this->cart->conditions->each(function ($condition) {
                    // If the condition is flagged to be deleted, delete it.
                    if ($condition->delete) {
                        $condition->delete();
                    // Otherwise ensure it has the right cart id set for it.
                    } else {
                        $condition->cart_id = $this->cart->id;
                        $condition->save();
                    }
                });
                // Save cart items.
                $this->cart->items->each(function ($item) {
                    // If the item is flagged to be deleted, delete it.
                    if ($item->delete) {
                        $item->attributes->delete();
                        $item->conditions->each->delete();
                        $item->delete();
                    // Otherwise ensure it has the right cart id set for it.
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
                            // If the condition is flagged to be deleted, delete
                            // it.
                            if ($condition->delete) {
                                $condition->delete();
                            // Otherwise ensure it has the right item id set for
                            // it.
                            } else {
                                $condition->item_id = $item->id;
                                $condition->save();
                            }
                        });
                    }
                });
            });
        } catch (\Exception $original_exception) {
            $message = 'Failed to save shopping cart items to storage.';
            throw new CartSaveException($message, 0, $original_exception);
        }
        // Dispatch 'saved' event.
        $this->fireEvent('saved', $this->cart);
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
    protected function fireEvent(string $eventName, ...$payload): ?array
    {
        return $this->events->dispatch(
            $this->getInstance() . '.' . $eventName,
            $payload
        );
    }

    /**
     * Pass requests to properties to underlying cart container.
     *
     * @param mixed $prop
     *
     * @return mixed
     */
    public function __get($prop)
    {
        return $this->cart->$prop;
    }
}

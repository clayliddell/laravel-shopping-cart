<?php

namespace clayliddell\ShoppingCart\Traits\Cart;

use Illuminate\Events\Dispatcher;

use clayliddell\ShoppingCart\EventCodes;
use clayliddell\ShoppingCart\Database\Models\{
    Cart as CartContainer,
    Item,
};

/**
 * Shopping cart item management implementation.
 */
trait ItemManagementTrait
{
    use ItemValidationTrait;

    /**
     * Cart container.
     */
    protected CartContainer $cart;

    /**
     * Event Dispatcher.
     */
    protected Dispatcher $events;

    /**
     * Check whether shopping cart has items with provided item IDs.
     *
     * @param array<string> $ids
     *   Shopping cart item IDs.
     *
     * @return bool
     *   Whether the shopping cart has all of the supplied items.
     */
    public function hasItem(string ...$ids): bool
    {
        return $this->cart->items->has(...$ids);
    }

    /**
     * Get shopping cart item with provided item ID.
     *
     * @param string $id
     *   Shopping cart item ID.
     *
     * @return Item|null
     *   The item belonging to the supplied ID or `null` on failure.
     */
    public function getItem(string $id): ?Item
    {
        return $this->cart->items->where('id', $id)->first();
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
        if ($this->events->dispatch('adding_item', $item) !== EventCodes::HALT_EXECUTION) {
            // Associate the newly created item with the cart.
            $item->cart()->associate($this->cart);
            $this->cart->items->add($item);
        }
        // Return newly created item.
        return $item;
    }

    /**
     * Remove item(s) from the shopping cart container.
     *
     * @param array<int> $ids
     *   Key(s) of shopping cart item(s) to be removed.
     */
    public function removeItem(int ...$ids): void
    {
        // Dispatch 'removing_items' event before proceeding; if
        // HALT_EXECUTION code is returned, prevent shopping cart from being
        // saved.
        if ($this->events->dispatch('removing_items', [$this->cart, $ids]) !== EventCodes::HALT_EXECUTION) {
            // Flag specified items for deletion.
            $this->cart->items->find($ids)->each(fn ($item) => $item->delete = true);
            // Dispatch 'removed_items' event.
            $this->events->dispatch('removed_items', [$this->cart, $ids]);
        }
    }

    /**
     * Get the number of items in cart.
     */
    public function countItems(): int
    {
        return $this->cart->items->count();
    }

    /**
     * Create shopping cart item and add it to cart container.
     *
     * @param int $sku_id
     *   Item sku,
     * @param int $quantity
     *   Item quantity.
     * @param array|null $attr
     *   Item attributes.
     *
     * @return Item
     *   Newly created item.
     */
    protected function createItem(int $sku_id, int $quantity, ?array $attr): Item
    {
        // Retrieve fully qualified path to item attributes model.
        $item_attr_model = config('shopping_cart.cart_item_attributes_model', '\App\ItemAttributes');
        // Make and eager load item attributes model.
        $attributes = $attr ? $item_attr_model::make($attr)->load() : null;
        // Create an item using the provided details.
        $item = Item::make([
            'cart_id'       => $this->cart->id,
            'sku_id'        => $sku_id,
            'quantity'      => $quantity,
        ])->load();
        // Associate attributes with item.
        if (isset($attributes)) {
            $item->attributes()->associate($attributes);
        }

        return $item;
    }
}

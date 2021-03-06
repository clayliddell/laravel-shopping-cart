<?php

namespace clayliddell\ShoppingCart\Traits\Cart;

use clayliddell\ShoppingCart\Database\Models\{
    Cart as CartContainer,
    Item,
};
use clayliddell\ShoppingCart\Exceptions\ItemValidationException;
use clayliddell\ShoppingCart\Validation\CartValidator;

/**
 * Shopping cart item validation implementation.
 */
trait ItemValidationTrait
{
    /**
     * Cart container.
     */
    protected CartContainer $cart;

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
    abstract protected function fireEvent(string $eventName, ...$payload): ?array;

    /**
     * Validate item properties.
     *
     * @param array $item
     *   Item properies.
     *
     * @throws ItemValidationException
     *   If the supplied item properties fail validation.
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
     * @throws ItemValidationException
     *   If the supplied item attributes fail validation.
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
}

<?php

namespace clayliddell\ShoppingCart\Traits\Cart;

use Illuminate\Events\Dispatcher;

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
     * Module config.
     */
    protected array $config;

    /**
     * Event Dispatcher.
     */
    protected Dispatcher $events;

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
        $this->events->dispatch('validating_item', [$this->cart, $item]);
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
        $item_attr_model = $this->config['cart_item_attributes_model'];
        // Validate shopping cart item properties.
        $validator = CartValidator::make($attr, $item_attr_model::rules());
        // Alert user if validation fails.
        if ($validator->fails()) {
            throw new ItemValidationException($validator->messages()->first());
        }
    }
}

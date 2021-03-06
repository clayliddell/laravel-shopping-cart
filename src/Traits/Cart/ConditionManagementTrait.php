<?php

namespace clayliddell\ShoppingCart\Traits\Cart;

use clayliddell\ShoppingCart\EventCodes;
use clayliddell\ShoppingCart\Database\Models\{
    Cart as CartContainer,
    Condition,
    ConditionType,
};

/**
 * Shopping cart condition management implementation.
 */
trait ConditionManagementTrait
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
     * Check whether this shopping cart has conditions with the provided names.
     *
     * @param array<string> $names
     *   Shopping cart condition names.
     *
     * @return bool
     *   Whether the shopping cart has all of the supplied conditions.
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
     *   The condition with the supplied name or `null` on failure.
     */
    public function getCondition(string $name): ?Condition
    {
        return $this->cart->conditions->where('name', $name)->first();
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
    protected function addCondition(ConditionType $condition_type, bool $validate = true): ?Condition
    {
        // Ensure procedure was not halted and condition passed validation.
        if (!$validate || $condition_type->validate($this->cart)) {
            // Create condition of type for cart.
            $condition = Condition::make([
                'cart_id' => $this->cart->id,
                'type_id' => $condition_type->id,
            ])->load();
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
     * Remove conditions from the shopping cart container.
     *
     * @param array<string> $ids
     *   IDs of the shopping cart conditions to be removed.
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
     * Process items in cart and add appropriate conditions.
     *
     * @return array<Condition>
     *   The conditions applied to the cart.
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
                $this->cart->items->map(fn ($item) => $item->addCondition($condition_type))->all(),
            );
        });

        return $conditions;
    }
}

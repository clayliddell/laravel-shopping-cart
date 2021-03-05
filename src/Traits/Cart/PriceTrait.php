<?php

namespace clayliddell\ShoppingCart\Traits;

use clayliddell\ShoppingCart\Database\Models\{
    Cart as CartContainer,
    ConditionType,
    Item,
};
use Illuminate\Database\Eloquent\Builder;

/**
 * Shopping cart price calculation implementation.
 */
trait PriceTrait
{
    /**
     * Cart container.
     */
    protected CartContainer $cart;

    /**
     * Calculate tax of the current shopping cart's content.
     *
     * @param float|null $subtotal
     *   The subtotal to use or null to re-calculate this cart's subtotal.
     *
     * @return float
     *   The tax total for this cart's content.
     */
    public function calculateTax(?float $subtotal = null): float
    {
        // Get all condition types of the tax category.
        $tax_types = ConditionType::whereHas(
            'category',
            fn (Builder $q) => $q->where('name', 'tax')
        )->pluck('id')->toArray();

        return $this->calculateConditions($subtotal, true, false, $tax_types);
    }

    /**
     * Calculate discount total of the current shopping cart's content.
     *
     * @param float|null $subtotal
     *   The subtotal to use or null to re-calculate this cart's subtotal.
     *
     * @return float
     *   The discount total for this cart's content.
     */
    public function calculateDiscounts(?float $subtotal = null): float
    {
        // Get all condition types of the discount category.
        $discount_types = ConditionType::whereHas(
            'category',
            fn (Builder $q) => $q->where('name', 'discount')
        )->pluck('id')->toArray();

        return $this->calculateConditions($subtotal, true, true, $discount_types);
    }

    /**
     * Calculate subtotal of the current shopping cart's content.
     *
     * @param bool $withConditions
     *   Whether to include conditions in the subtotal.
     * @param array<int> $types
     *   IDs of condition types to apply to total.
     *
     * @return float
     *   The subtotal for this cart's content.
     */
    public function calculateSubtotal(bool $withConditions = false, array $types = []): float
    {
        // Initialize cart subtotal to 0.
        $subtotal = 0;
        // Add cart item's prices to subtotal.
        foreach ($this->cart->items as $item) {
            $subtotal += $item->sku->price;
        }
        // If any condition types have been specified which need to be included,
        // add them to the subtotal.
        if ($withConditions) {
            $subtotal += $this->calculateConditions($subtotal, true, true, $types);
        }
        return $subtotal;
    }

    /**
     * Calculate total price of the current shopping cart's content.
     *
     * @return float
     *   The total price for this cart's content.
     */
    public function calculateTotal(): float
    {
        // Add the cart's subtotal to it's condition total to get the total
        // amount.
        $subtotal = $this->calculateSubtotal(true);
        return $subtotal + $this->calculateTax($subtotal);
    }

    /**
     * Calculate shopping cart's totals.
     *
     * @return array<float>
     *   The conditions, subtotal, tax, and overall total of this cart's
     *   content.
     */
    public function calculateTotals(): array
    {
        // Calculate the carts non-tax conditions total.
        $conditions = $this->calculateConditions();
        // Calculate the carts sub-total with out conditions, then add the
        // pre-calculated conditions.
        $subtotal = $this->calculateSubtotal() + $conditions;
        // Calculate the carts tax total.
        $tax = $this->calculateTax($subtotal);
        // Calculate the carts grand total.
        $total = $subtotal + $tax;

        // Combine all of these totals into an array.
        return compact('conditions', 'subtotal', 'tax', 'total');
    }

    /**
     * Calculate total of the specified condition(s) in the shopping cart.
     *
     * This total does not include tax conditions unless explicitly declared in
     * `$types`.
     * The calculation of this total can be restricted using the
     * `$include_cart_condtions` and `$include_item_conditions` flags, by
     * restricting the conditions types the total should be calculated for using
     * `$types`, and by specifying `$additional_conditions` which should be
     * applied.
     *
     * @param bool $include_cart_conditions
     *   Whether to include cart conditions in the total.
     * @param bool $include_item_conditions
     *   Whether to include item conditions in the total.
     * @param array<int> $types
     *   Ids of condition types to be included (Leave empty to include all
     *   non-tax types).
     *
     * @param array<Condition> $additional_conditions
     *   Additional conditions which are not in the cart to include.
     *
     * @return float
     *   Total amount.
     */
    public function calculateConditions(
        float $subtotal = null,
        bool $include_cart_conditions = true,
        bool $include_item_conditions = true,
        array $types = [],
        array $additional_conditions = []
    ): float {
        $subtotal ??= $this->calculateSubtotal();
        // Initialize condition total to 0.
        $condition_total = 0;
        // If told to include cart conditions in the conditions total, loop
        // through all cart level conditions and add them to the cart.
        if ($include_cart_conditions) {
            // Get all condition types not of the tax category.
            $non_tax_types = ConditionType::whereHas(
                'category',
                fn (Builder $q) => $q->where('name', '!=', 'tax')
            )->pluck('id')->toArray();
            foreach ($this->cart->conditions as $condition) {
                // If condition types have been specified, ensure that only
                // conditions of these condition types are added to the
                // condition total. Otherwise, allow conditions of all types
                // except tax conditions.
                if (in_array($condition->type->id, $types ?: $non_tax_types, true)) {
                    // If the condition is a percentage based condition,
                    // multiply the subtotal by the conditions percentage in
                    // order to determine the conditions values.
                    // Otherwise just add the condition's total.
                    $condition_total += $condition->type->percentage ?
                        $subtotal * $condition->type->value : $condition->type->value;
                }
            }
        }
        // If told to include item conditions in the conditions total, loop
        // through all item level conditions and add them to the cart.
        if ($include_item_conditions) {
            foreach ($this->cart->items as $item) {
                foreach ($item->conditions as $condition) {
                    // If condition types have been specified, ensure that only
                    // conditions of these condition types are added to the
                    // condition total.
                    if (empty($types) || in_array($condition->type->id, $types, true)) {
                        // If the condition stacks, apply the condition for each
                        // item in the cart.
                        $condition_total += $item->calculateConditionTotal($condition);
                    }
                }
            }
        }
        // If additional conditions are supplied, add them to the condition
        // total.
        foreach ($additional_conditions as $condition) {
            $condition_total += $condition->type->value;
        }
        return $condition_total;
    }
}

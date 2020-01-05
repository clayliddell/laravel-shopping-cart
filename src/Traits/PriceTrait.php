<?php

namespace clayliddell\ShoppingCart\Traits;

/**
 * Shopping cart price calculation implementation.
 */
trait Price
{
    /**
     * Cart container.
     *
     * @var CartContainer
     */
    protected CartContainer $cart;

    /**
     * Calculate tax of the current shopping cart's content.
     *
     * @return float
     */
    public function calculateTax(?float $subtotal = null): float
    {
        $subtotal ??= $this->calculateSubtotal();

        // Calculate sum of all tax condition decimal values.
        $tax = array_sum($this->cart->conditions->where('type', 'tax')
            ->pluck('value')->all());

        return $subtotal * $tax;
    }

    /**
     * Calculate discount total of the current shopping cart's content.
     *
     * @return float
     */
    public function calculateDiscountTotal()
    {
        $this->calculateConditionTotal(true, true, ['discount']);
    }

    /**
     * Calculate total with the specified condition types applied.
     *
     * @param  bool  $withConditions Whether to include conditions in subtotal.
     * @param  array $types          Condition types to apply to total.
     * @return float
     */
    public function calculateSubtotal(
        bool $withConditions = true,
        array $types = []
    ) {
        // Initialize cart subtotal to 0.
        $total = 0;
        // Add cart item's prices to subtotal.
        foreach ($this->cart->items as $item) {
            $total += $item->price;
        }
        // If any condition types have been specified which need to be included,
        // add them to the subtotal.
        if ($withConditions) {
            $total += $this->calculateConditionTotal(true, true, $types);
        }
    }

    /**
     * Calculate total price of the current shopping cart's content.
     *
     * @return float
     */
    public function calculateTotal()
    {
        // Add the cart's subtotal to it's condition total to get the total
        // amount.
        $subtotal = $this->calculateSubtotal();
        return $subtotal + $this->calculateTax($this->calculateSubtotal());
    }

    /**
     * Calculate total of the specified condition(s) in the shopping cart.
     *
     * This total does not include tax conditions unless explicitly declared in
     * $types.
     * The calculation of this total can be restricted using the
     * `$include_cart_condtions` and `$include_item_conditions` flags, by
     * restricting the conditions types the total should be calculated for using
     * `$types`, and by specifying `$additional_conditions` which should be
     * applied.
     * @param  bool     $include_cart_conditions Whether to include cart
     *                  conditions in the total.
     * @param  bool     $include_item_conditions Whether to include item
     *                  conditions in the total.
     * @param  string[] $types Condition types to be included (Leave empty to
     *                  include all types).
     * @param  CartCondition[] $additional_conditions Additional conditions
     *                  which are not in the cart to include in the total.
     * @return float    Total amount.
     */
    public function calculateConditionTotal(
        bool $include_cart_conditions = true,
        bool $include_item_conditions = true,
        array $types = [],
        array $additional_conditions = []
    ) {
        // If condition type names have been provided, retrieve the ids for the
        // specified types.
        $types = $types ?: ConditionTypes::whereIn('type', $types)->pluck('id');
        // Initialize condition total to 0.
        $total = 0;
        // If told to include cart conditions in the conditions total, loop
        // through all cart level conditions and add them to the cart.
        if ($include_cart_conditions) {
            foreach ($this->cart->conditions as $condition) {
                // Get id of tax condition type.
                $tax_type_id = ConditionTypes::where('type', 'tax')->first()->id;
                // If condition types have been specified, ensure that only
                // conditions of these condition types are added to the
                // condition total. Otherwise, allow conditions of all types
                // except tax conditions.
                if (
                    (empty($types) && $condition->type->id != $tax_type_id) ||
                    in_array($condition->type->id, $types, true)
                ) {
                    // Add condition amount to conditions total.
                    $total += $condition->amount;
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
                        $quantity = $condition->stacks ? $item->quantity : 1;
                        $total += $condition->amount * $quantity;
                    }
                }
            }
        }
        // If additional conditions are supplied, add them to the condition
        // total.
        foreach ($additional_conditions as $condition) {
            $total += $condition->amount;
        }
        return $total;
    }
}

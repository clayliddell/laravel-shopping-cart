<?php

namespace clayliddell\ShoppingCart\Traits;

trait Price
{
    protected $cart;

    /**
     * Calculate tax total of the current shopping cart's content.
     *
     * @return float
     */
    public function calculateTaxTotal() {}

    /**
     * Calculate discount total of the current shopping cart's content.
     *
     * @return float
     */
    public function calculateDiscountTotal() {}

    /**
     * Calculate total before tax of the current shopping cart's content.
     *
     * @return float
     */
    public function calculateSubtotal() {}

    /**
     * Calculate total price of the current shopping cart's content.
     *
     * @return float
     */
    public function calculateTotal() {}

    /**
     * Calculate total of the specified condition(s) in the shopping cart.
     *
     * This total should include the total amount associated with all of the
     * specified condition types.
     * @param array $types Condition types.
     * @param bool $include_cart_conditions Whether to include cart conditions in the total.
     * @param bool $include_item_conditions Whether to include item conditions in the total.
     * @return float Total amount.
     */
    public function calculateConditionTotal(
        $include_cart_conditions = true,
        $include_item_conditions = true,
        array $conditions = []
    ) {

    }
}

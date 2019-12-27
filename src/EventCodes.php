<?php

namespace clayliddell\ShoppingCart;

/**
 * Shopping cart event codes container.
 */
class EventCodes
{
    /**
     * Code used to halt containing code block execution.
     *
     * @var int
     */
    public const HALT_EXECUTION = 1000;

    /**
     * Code used to signify that the entire cart is being cleared out.
     *
     * @var int
     */
    public const CLEARING_CART = 1001;

    /**
     * Code used to signify that only the cart items are being cleared out.
     *
     * @var int
     */
    public const CLEARING_ITEMS = 1002;

    /**
     * Code used to signify that only the item level conditions are being
     * cleared out.
     *
     * @var int
     */
    public const CLEARING_ITEM_CONDITIONS = 1003;

    /**
     * Code used to signify that only the cart level conditions are being
     * cleared out.
     *
     * @var int
     */
    public const CLEARING_CART_CONDITIONS = 1004;
}

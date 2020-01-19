<?php

namespace clayliddell\ShoppingCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Shopping Cart Facade.
 */
class CartFacade extends Facade
{
    /**
     * @inheritDoc
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cart';
    }
}

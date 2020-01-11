<?php

namespace clayliddell\ShoppingCart\Facades;

use Illuminate\Support\Facades\Facade;
use clayliddell\ShoppingCart\ShoppingCartServiceProvider;

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
        return ShoppingCartServiceProvider::class;
    }
}

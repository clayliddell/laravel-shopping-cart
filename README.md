# LCart
[![Code Climate](https://codeclimate.com/github/codeclimate/codeclimate/badges/gpa.svg)](https://codeclimate.com/github/codeclimate/codeclimate)

## Overview

A shopping cart implementation designed for use with the Laravel framework.

## Why?

There are already quite a few Open Source shopping cart packages out there designed specifically for integration with the Laravel framework. So, you may be wondering, “why create another?”

The most popular Laravel shopping cart package out there is the one created by darryldecode (https://github.com/darryldecode/laravelshoppingcart). It’s a fairly simple shopping cart implementation with support for cart sessions, stackable items, and cart/item scoped price conditions. Darryldecode’s package seems well implemented for smaller sites which just need a basic shopping cart implementation, however there’s still quite a bit that it’s lacking for use on a medium-large scale ecommerce site.

## So, what more does LCart have to offer?

### Support for multiple database backends
For small ecommerce sites, using a standard SQL database for storing user cart session information is fine. However, for developers on medium-large scale platforms, LCart offers support for in-memory key-value databases such as Redis.
### Automatic application of cart/item conditions
Want to automatically apply a 10% discount to an item in a user’s cart if it’s quantity rises above 5? We can do that. Want to automatically apply a sales tax rate to a users cart depending on where they’re located? We’ve got you covered.
### Auto-combining item stacks
Let’s say Jane adds 2 coasters to her cart, but before checking out, adds 2 more coasters. Our robust API prevents you from having to track down the original item set in the cart and increase its quantity.  Simply add the new item set as you normally would, and the quantity will be automatically adjusted.
### Cart sessions for anonymous users
Want to allow anonymous users to shop on your site without creating an account? No problem!

## That sounds great! How do I get started?

### Installation

1. Make sure you have composer installed.
2. In the root of your project run: `composer require clayliddell/lcart`.
3. In `config/app.php` add the following line to `’providers’`: `clayliddell\ShoppingCart\ShoppingCartServiceProvider::class`.
4. Copy the necessary database migrations and publish the config by running: `php artisan vendor:publish --provider="clayliddell\ShoppingCart\ShoppingCartServiceProvider" --tag="config"`.

### Basic Usage

```php
$cart  = \App::make(Cart::class, [
    ‘session’ => Auth::id(),
    ‘Instance’ => ‘cart’,
]);

$sku = ‘AZ8981A2A’; // coasters
$cart->addItem($sku, 2);
print_r($cart->toArray());
// [
//     ‘items’ => [
//        ‘sku’ => ‘AZ8981A2A’,
//        ‘quantity’ => 2,
//     ]
// ]
$cart->addItem($sku, 1);
print_r($cart->toArray());
// [
//     ‘items’ => [
//        ‘sku’ => ‘AZ8981A2A’,
//        ‘quantity’ => 3,
//     ]
// ]
```

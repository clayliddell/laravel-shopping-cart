<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use clayliddell\ShoppingCart\Models\ConditionBase;

/**
 * Service provider for shopping cart package.
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge default config with user provided config file.
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'shopping_cart');
        // Initialize cart singleton.
        $this->app->singleton('cart', function ($app) {
            // Retrieve event dispatcher class from config for handling events.
            $eventsClass = config('shopping_cart.events');
            // Initialize instance of events class.
            $events = $eventsClass ? new $eventsClass() : $app['events'];
            // Retrieve instance name for identifying dispatched events.
            $instance = config('shopping_cart.default_instance') ??
                'cart';
            // Default session or cart identifier. This will be overridden when
            // when adding a cart for a specific session/user using
            // Cart::session($session). Session's must be a unique string
            // used to bind a cart to a specific user, e.g. a user ID.
            $session = config('shopping_cart.default_session') ??
                'C97ROP6UDdemJu8M';
            // Create shopping cart instance.
            return new Cart(
                $instance,
                $session,
                $events,
                config('shopping_cart')
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Ensure that the laravel helper function 'config_path' used to resolve
        // the path to config files is available.
        if (function_exists('config_path')) {
            // Publish default config file to to laravel config directory.
            $this->publishes([
                __DIR__ . '/config/shopping_cart.php' => config_path('shopping_cart.php'),
            ], 'config');
        }

        // Hook into cart condition models (CartCondition, ItemCondition) saving
        // event.
        ConditionBase::saving(function () {
            // If conditions_persistent is set to false, prevent cart conditions
            // from being saved.
            if (!config('shopping_cart.conditions_persistent', true)) {
                return false;
            };
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }
}

<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
            $instance_name = config('shopping_cart.default_instance_name') ??
                'cart';
            // Retrieve DB connection name for storing shopping cart items.
            $connection_name = config('shopping_cart.connection') ??
                'shopping_cart';
            // Default session or cart identifier. This will be overridden when
            // when adding a cart for a specific session/user using
            // Cart::session($sessionKey). Session Key's must be a unique string
            // used to bind a cart to a specific user, e.g. a user ID.
            $session_id = config('shopping_cart.default_session_key') ??
                'C97ROP6UDdemJu8M';
            // Create shopping cart instance.
            return new Cart(
                $events,
                $instance_name,
                $session_id,
                $connection_name,
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

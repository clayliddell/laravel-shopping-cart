<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use clayliddell\ShoppingCart\Database\Models\ConditionBase;

/**
 * Service provider for shopping cart package.
 */
class ShoppingCartServiceProvider extends BaseServiceProvider
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
            $instance = config('shopping_cart.default_instance') ?? 'cart';
            // Default session or cart identifier. This will be overridden when
            // when adding a cart for a specific session/user using
            // Cart::session($session). Session's must be a unique string
            // used to bind a cart to a specific user, e.g. a user ID.
            // If "Use user id for session" is set to `true` in shopping cart
            // config, then the user_id of the current user will be used for the
            // default session.
            $session = config('shopping_cart.use_user_id_for_session') ? Auth::id() :
                config('shopping_cart.default_session', 'C97ROP6UDdemJu8M');

            // Create shopping cart instance.
            return new Cart(
                $instance,
                $session,
                $events
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
        // Publish default config file to to laravel config directory.
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('shopping_cart.php'),
        ], 'config');

        // Define item attributes database migration file path.
        $migration_file = 'create_item_attributes_table.php';
        // Publish item attributes migration.
        if (empty(File::glob(database_path("migrations/*_$migration_file")))) {
            // Get current timestamp in migration compatible format.
            $timestamp = date('Y_m_d_His', time());
            // Define destination migration file path.
            $migration = database_path("migrations/{$timestamp}_{$migration_file}");
            // Publish item attributes migration.
            $this->publishes([
                __DIR__ . "/../database/migrations/$migration_file.stub" => $migration,
            ], 'migrations');
        }

        // Define item attributes model file path.
        $attr_file = 'ItemAttributes.php';
        // Publish item attributes model.
        $this->publishes([
            __DIR__ . "/Database/Models/$attr_file.stub" => app_path($attr_file),
        ], 'models');

        // Load cart database migrations.
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // If conditions_persistent is set to false, prevent cart conditions
        // from being saved.
        if (!config('shopping_cart.conditions_persistent', true)) {
            // Hook into cart condition models (CartCondition, ItemCondition)
            // saving event.
            ConditionBase::saving(function () {
                    return false;
            });
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

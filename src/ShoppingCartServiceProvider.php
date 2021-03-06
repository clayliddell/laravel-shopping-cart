<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;

use clayliddell\ShoppingCart\Database\Models\Condition;
use clayliddell\ShoppingCart\Interfaces\CartInterface;

/**
 * Service provider for shopping cart package.
 */
class ShoppingCartServiceProvider extends BaseServiceProvider
{
    /**
     * Base project path.
     *
     * @var string
     */
    protected string $project_path;

    /**
     * Base database path.
     *
     * @var string
     */
    protected string $base_database_path;

    /**
     * Creates a new service provider instance.
     *
     * @param Application $app
     *   The application instance.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        // Call parent constructor.
        parent::__construct($app);
        // Define project path.
        $this->project_path = __DIR__ . '/..';
        // Define base module database path.
        $this->base_database_path = "{$this->project_path}/src/Database";
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge default config with user provided config file.
        $this->mergeConfigFrom("{$this->project_path}/config/config.php", 'shopping_cart');

        // Bind the cart interface to the cart implementation for dependency
        // injection.
        $this->app->bind(CartInterface::class, Cart::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish all module files.
        $this->publishConfig();
        $this->publishModels();
        $this->publishMigrations();
        $this->publishSeeders();

        // Load cart database migrations.
        $this->loadMigrationsFrom("{$this->project_path}/database/migrations");

        // If conditions_persistent is set to false, prevent cart conditions
        // from being saved.
        if (!config('shopping_cart.conditions_persistent', true)) {
            // Hook into cart condition model saving event.
            Condition::saving(fn ($condition) => false);
        }
    }

    /**
     * Publish module config files.
     *
     * @return void
     */
    protected function publishConfig(): void
    {
        // Define base config path for module.
        $config_path = "{$this->project_path}/config";
        // Publish default config file to to laravel config directory.
        $this->publishes([
            "{$config_path}/config.php" => config_path('shopping_cart.php'),
        ], 'config');
    }

    /**
     * Publish module models.
     *
     * @return void
     */
    protected function publishModels(): void
    {
        // Define base path for all models used by module.
        $base_model_path = "{$this->base_database_path}/Models";
        // Fetch models which are to be published.
        $model_paths = array_flip(File::glob("{$base_model_path}/*.stub"));
        // Store source and destination model paths to publish.
        array_walk($model_paths, function (&$destination, $source) {
            $destination = app_path(rtrim(basename($source), '.stub'));
        });
        // Publish all models.
        $this->publishes($model_paths, 'models');
    }

    /**
     * Publish module migrations.
     *
     * @return void
     */
    protected function publishMigrations(): void
    {
        // Define base migration path for all migrations used by module.
        $base_migration_path = "{$this->project_path}/database/migrations";
        // Fetch database migration files which are to be published.
        $migration_paths = File::glob("{$base_migration_path}/*.stub");
        // Filter out all migration files which have already been published.
        $migration_paths = array_filter(
            $migration_paths,
            function ($migration_source) {
                // Determine destination migration file name.
                $migration_file = rtrim(basename($migration_source), '.stub');
                // Check if the destination migration file already exists.
                $migration_destination = File::glob(database_path("migrations/*_{$migration_file}"));
                // Filter out the current migration file if it already exists.
                return empty($migration_destination);
            }
        );
        // Get the current date timestamp in the laravel migration file format.
        $timestamp = date('Y_m_d_His', time());
        $migration_paths = array_flip($migration_paths);
        // Store source and destination migration paths to publish.
        array_walk(
            $migration_paths,
            fn (&$destination, $source) => $destination = database_path("migrations/{$timestamp}_" .
                rtrim(basename($source), '.stub'))
        );
        // Publish all migrations.
        $this->publishes($migration_paths);
    }

    /**
     * Publish module seeders.
     *
     * @return void
     */
    protected function publishSeeders(): void
    {
        // Define base seeder path for all seeders used by module.
        $base_seeder_path = "{$this->base_database_path}/Seeds";
        // Fetch seeder files which are to be published.
        $seeder_paths = array_flip(File::glob("{$base_seeder_path}/*.stub"));
        // Store source and destination seeder paths to publish.
        foreach ($seeder_paths as $source => &$destination) {
            $destination = database_path('seeds/' . rtrim(basename($source), '.stub'));
        }
        // Publish seeder files.
        $this->publishes($seeder_paths, 'seeds');
    }

    /**
     * {@inheritDoc}
     */
    public function provides(): array
    {
        return [];
    }
}

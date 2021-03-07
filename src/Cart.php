<?php

namespace clayliddell\ShoppingCart;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Events\Dispatcher;

use clayliddell\ShoppingCart\Database\Models\Cart as CartContainer;
use clayliddell\ShoppingCart\Traits\Cart\{
    ContentManagementTrait,
    PriceTrait,
};

/**
 * Shopping cart implementation.
 */
class Cart implements Arrayable
{
    use ContentManagementTrait;
    use PriceTrait;

    /**
     * Laravel Auth factory.
     */
    protected AuthFactory $auth;

    /**
     * Cart container.
     */
    protected CartContainer $cart;

    /**
     * Module config.
     */
    protected array $config;

    /**
     * Laravel event dispatcher.
     */
    protected Dispatcher $events;

    /**
     * Creates a Cart object.
     *
     * @param AuthFactory $auth
     *   Laravel auth factory.
     * @param Dispatcher $events
     *   Laravel event dispatcher.
     * @param ConfigRepository $config
     *   Laravel config repository.
     * @param string|null $session
     *   Cart session name.
     * @param string|null $instance
     *   Cart instance name.
     */
    public function __construct(
        AuthFactory $auth,
        Dispatcher $events,
        ConfigRepository $config,
        string $session = NULL,
        string $instance = NULL
    ) {
        // Store the event dispatcher service.
        $this->events = $events;
        // Retrieve shopping cart settings from config.
        $this->config = $config->get('shopping_cart');

        // If no session was specified, determine the session to use for this
        // cart instance.
        if (!isset($session)) {
            // If cart user session pairing is enabled, set the session name to
            // the current user's ID.
            if ($this->config['use_user_id_for_session']) {
                $session = $auth->id();
            }
            // Otherwise, use the default session name.
            $session ??= $this->config['default_session'];
        }
        // If no instance was specified, retrieve the default instance name from
        // config.
        $instance ??= $this->config['default_instance'];

        // Initialize cart model.
        $this->cart = CartContainer::firstOrNew([
            'session'  => $session,
            'instance' => $instance,
        ]);
        // Dispatch 'constructed' event.
        $this->events->dispatch('constructed', $this);
    }

    /**
     * Destroys a Cart object.
     */
    public function __destruct()
    {
        // If `$saveOnDestruct` flag is set to `true`, save the cart.
        if ($this->config['save_on_destruct']) {
            $this->cart->save();
        }
    }

    /**
     * Get all shopping cart sessions.
     */
    public static function getSessions(): array
    {
        return CartContainer::all()->pluck('session');
    }

    /**
     * Get all instances associated with a cart session.
     *
     * @param string $session
     *   The cart session to search by.
     *
     * @return array<string>
     *   Instances for cart's belonging to the supplied session.
     */
    public static function getInstances(string $session): array
    {
        return CartContainer::where('session', $session)->pluck('instance');
    }

    /**
     * Pass requests for properties to underlying cart container.
     *
     * @param mixed $prop
     *   Property being requested.
     *
     * @return mixed
     *   Value of cart property.
     */
    public function __get($prop)
    {
        return $this->cart->{$prop};
    }

    /**
     * Convert cart items and conditions into an array.
     */
    public function toArray(): array
    {
        return [
            'items' => $this->cart->items->toArray(),
            'conditions' => $this->cart->conditions->toArray(),
        ];
    }
}

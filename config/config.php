<?php

return [
    // Database connection used for storing shopping cart items and conditions.
    'connection' => 'shopping_cart',
    // Default shopping cart instance name.
    'default_instance' => 'cart',
    // Default shopping cart session id.
    'default_session' => 'C97ROP6UDdemJu8M',
    // Default shopping cart event handler class.
    'events' => null,
    // Whether to store conditions in database, or only store items.
    'conditions_persistent' => true,
    // If set to true, invalid conditions which are applied will fail silently.
    'ignore_condition_validation' => true,
];

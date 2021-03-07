<?php

return [
    // Database connection used for storing shopping cart items and conditions.
    'connection' => 'shopping_cart',
    // Default shopping cart instance name.
    'default_instance' => 'cart',
    // Default shopping cart session id.
    'default_session' => 'C97ROP6UDdemJu8M',
    // Whether to use the current user's id for the session.
    'use_user_id_for_session' => true,
    // Whether to store conditions in database, or only store items.
    'conditions_persistent' => true,
    // Namespaced reference to Cart Item Attributes Model. (defaults to `App\ItemAttributes`).
    'cart_item_attributes_model' => null,
    // Save cart content on cart destruct.
    'save_cart_on_destruct' => true,
];

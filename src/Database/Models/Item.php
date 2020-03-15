<?php

namespace clayliddell\ShoppingCart\Database\Models;

use clayliddell\ShoppingCart\Database\Interfaces\HasConditions;

/**
 * Shopping cart item container.
 */
class Item extends CartBase implements HasConditions
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array<string>
     */
    public static $rules = [
        'cart_id'       => 'required|exists:$connection.carts,id',
        'sku_id'        => 'required|exists:$connection.item_skus,id',
        'attributes_id' => 'nullable|exists:$connection.item_attributes,id',
        'quantity'      => 'required|integer|min:1',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'cart_id',
        'sku_id',
        'attributes_id',
        'quantity',
    ];

    /**
     * Attributes to include when fetching relationship.
     *
     * @var array<string>
     */
    protected $with = [
        'sku',
        'conditions',
        'attributes',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function sku()
    {
        return $this->belongsTo(ItemSku::class);
    }

    public function conditions()
    {
        return $this->hasMany(Condition::class);
    }

    public function attributes()
    {
        $model = config('shopping_cart.cart_item_attributes_model', 'App\ItemAttributes');
        return $this->belongsTo($model);
    }

    /**
     * Attempt to apply shopping cart condition to item.
     *
     * @param Condition $condition
     *   Condition being applied to the item.
     * @param bool $validate
     *   Whether to validate the supplied item condition before application.
     *
     * @return Condition|false
     *   Condition which was applied or false on failure.
     */
    public function addCondition(
        Condition $condition,
        bool $validate = true
    ) {
        // Validate condition if necessary; in the case it fails validation,
        // return `false`.
        if ($validate && !$condition->validate($this)) {
            $condition = false;
        }
        // Dispatch 'adding' event before proceeding; if HALT_EXECUTION code is
        // returned, prevent shopping cart condition from being added to cart.
        if (
            $this->fireEvent('adding', $condition) !== EventCodes::HALT_EXECUTION ||
            $condition = false
        ) {
            // Associate newly created condition with cart.
            $this->conditions->add($condition);
        }
        return $condition;
    }
}

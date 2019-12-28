<?php

namespace clayliddell\ShoppingCart;

class Cart extends CartBase
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array
     */
    public static $rules = [
        'session_id'    => 'required|string',
        'instance_name' => 'required|string',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_id',
        'instance_name',
    ];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the item associated with this condition.
     *
     * @return void
     */
    public function items()
    {
        return $this->hasMany('Model\Item');
    }

    /**
     * Get the condition type associated with this condition.
     *
     * @return void
     */
    public function conditions()
    {
        return $this->hasMany('Models\CartCondition');
    }

    /**
     * Clear all shopping cart items and conditions.
     *
     * @return void
     */
    public function clear()
    {
        $this->clearItems();
        $this->clearCartConditions();
    }

    /**
     * Remove all items from cart.
     *
     * @return void
     */
    public function clearItems()
    {
        foreach ($this->items as $item) {
            $item->delete();
        }
    }

    /**
     * Remove all conditions from items in cart.
     *
     * @return void
     */
    public function clearItemConditions()
    {
        foreach ($this->items as $item) {
            foreach ($item->conditions as $condition) {
                $condition->delete();
            }
        }
    }

    /**
     * Remove all cart conditions.
     *
     * @return void
     */
    public function clearConditions()
    {
        foreach ($this->conditions as $condition) {
            $condition->delete();
        }
    }
}

<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart item type.
 */
class ItemType extends CartBase
{
    /**
     * @inheritDoc validation rules.
     *
     * @var array
     */
    public static $rules = [
        'type' => 'required|string',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
    ];

    /**
     * Get all conditions which are of this condition type.
     *
     * @return void
     */
    public function items()
    {
        $this->hasMany('Model\Item');
    }
}
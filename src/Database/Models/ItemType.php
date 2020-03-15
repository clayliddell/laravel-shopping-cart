<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart item type.
 */
class ItemType extends CartBase
{
    /**
     * {@inheritDoc} validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get all conditions which are of this condition type.
     *
     * @return void
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }
}

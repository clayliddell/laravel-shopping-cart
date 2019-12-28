<?php

namespace clayliddell\ShoppingCart\Models;

/**
 * Shopping cart item container.
 */
class Item extends CartBase
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array
     */
    public static $rules = [
        'cart_id'  => 'required|numeric',
        'type_id'  => 'required|numeric',
        'name'     => 'required|string',
        'price'    => 'required|numeric',
        'quantity' => 'required|numeric|min:1',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cart_id',
        'type_id',
        'name',
        'price',
        'quantity',
    ];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function cart()
    {
        $this->belongsTo('Models\Cart');
    }

    public function type()
    {
        $this->belongsTo('Model\ItemType');
    }

    public function condition()
    {
        $this->hasMany('Models\ItemCondition');
    }
}

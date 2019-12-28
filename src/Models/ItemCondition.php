<?php

namespace clayliddell\ShoppingCart\Models;

/**
 * Shopping cart item condition.
 */
class ItemCondition extends ConditionBase
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name'       => 'required|string',
        'item_id'    => 'required|numeric',
        'type_id'    => 'required|numeric',
        'value'      => 'required|numeric|if:percentage,true,==,max:1',
        'percentage' => 'required|boolean',
        'stacks'     => 'boolean',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'item_id',
        'type_id',
        'value',
        'percentage',
        'stacks',
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
    public function item()
    {
        return $this->belongsTo('Model\Item');
    }
}

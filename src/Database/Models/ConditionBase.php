<?php

namespace clayliddell\ShoppingCart\Database\Models;

/**
 * Shopping cart item container.
 */
abstract class ConditionBase extends CartBase
{
    /**
     * Shopping cart item validation rules.
     *
     * @var array
     */
    public static $rules = [
        'name'       => 'required|string',
        'type_id'    => 'required|id',
        'value'      => 'required|numeric',
        'percentage' => 'required|bool',
        'stacks'     => 'boolean',
    ];

    /**
     * Attributes which are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type_id',
        'value',
        'stacks',
    ];

    /**
     * Get the condition type associated with this condition.
     *
     * @return void
     */
    public function type()
    {
        return $this->hasOne('Models\ConditionType');
    }
}

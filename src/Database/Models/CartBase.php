<?php

namespace clayliddell\ShoppingCart\Database\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Shopping cart item base model.
 */
abstract class CartBase extends Model
{
    /**
     * DB connection name to be used for model.
     *
     * @var string
     */
    protected $connection;

    /**
     * Shopping cart item validation rules.
     *
     * @var array<string>
     */
    public static $rules = [];

    /**
     * Flag to deliniate whether the model should be deleted.
     *
     * @var boolean
     */
    protected $delete = false;

    /**
     * @inheritDoc
     */
    public function __construct(array $attributes = [])
    {
        // Call parent constructor.
        parent::__construct($attributes);
        // Set the connection to be used for this migration to whatever
        // connection is set in the shopping cart config file.
        $this->connection = config('shopping_cart.connection');
    }

    /**
     * Get formatted validation rules for model.
     *
     * @return array<string>
     */
    public static function rules()
    {
        // Retrieve shopping cart database connection.
        $cart_connection = config('shopping_cart.connection');
        // Specify database connection in rules in neccessary.
        $rules = str_replace('$connection', $cart_connection, self::$rules);

        return $rules;
    }

    /**
     * Set the delete attribute.
     *
     * @param boolean $delete
     *
     * @return void
     */
    public function setDeleteAttribute(bool $delete)
    {
        $this->delete = $delete;
    }

    /**
     * Get the delete attribute.
     *
     * @param boolean $delete
     *
     * @return void
     */
    public function getDeleteAttribute()
    {
        return $this->delete;
    }

    /**
     * Eager load relations on the model.
     *
     * @param array|string $relations
     *
     * @return self
     */
    public function load($relations = [])
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        // Retrieve default relationships to eager load.
        if (empty($relations)) {
            $relations = $this->with;
        }

        $query = $this->newQuery()->with($relations);

        $query->eagerLoadRelations([$this]);

        return $this;
    }
}

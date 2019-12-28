<?php

namespace clayliddell\ShoppingCart\Validation;

use Illuminate\Validation\Validator;
use clayliddell\ShoppingCart\Traits\IfValidationRule;

class CartValidator extends Validator
{
    use IfValidationRule;
}

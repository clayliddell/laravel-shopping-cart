<?php

namespace clayliddell\ShoppingCart\Validation;

use Illuminate\Support\Facades\Validator;
use clayliddell\ShoppingCart\Traits\IfValidationRule;

class CartValidator extends Validator
{
    use IfValidationRule;
}

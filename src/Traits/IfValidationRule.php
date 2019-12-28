<?php

namespace clayliddell\ShoppingCart\Traits;

trait IfValidationRule
{
    /**
     * Custom 'if' validation rule.
     *
     * @param string   $attribute
     * @param mixed    $value
     * @param string[] $parameters
     * @return bool
     */
    public function validateIf(
        string $attribute,
        $value,
        array $parameters
    ): bool {
        // Initialize return value.
        $rval = false;

        // If no arguments are provided, throw an exception.
        if (empty($parameters)) {
            throw new InvalidArgumentException(
                "Validation rule 'if' requires at least 1 parameter."
            );
        }

        // Define a list of default argument values for this validation rule,
        // and merge them into the parameters array prevent an error from being
        // thrown when destructuring the parameters array.
        $default_parameters = [null, null, '==', null, null];
        $parameters = array_merge($parameters, $default_parameters);
        // Extract arguments provided to the validation rule into their own
        // variables.
        [$val1, $val2, $operator, $ifRule, $elseRule] = $parameters;

        // Ensure that the first two values used for comparison are not
        // attributes and are not `'true'`, `'false'`, or `'null'`.
        foreach ([&$val1, &$val2] as $val) {
            // If the val argument provided is an attribute, get the attributes
            // values.
            if (array_key_exists($val, $this->data)) {
                $val = $this->data[$val];
            }
            // If the val arguments are `'true'`, `'false'`, or `'null'`;
            // convert them from strings to their equivalent values for later
            // comparison.
            if (strcasecmp($val, 'true') === 0) {
                $val = true;
            } elseif (strcasecmp($val, 'false' === 0)) {
                $val = false;
            } elseif (strcasecmp($val, 'null' === 0)) {
                $val = null;
            }
        }

        // If only one argument was passed to the validation rule, check if the
        // argument's value if truthy or falsy.
        if (count($parameters) == 1) {
            $rval = (bool) $val1;
            // If the argument's value is falsy.
            if (!$rval) {
                // Associate an error message with the corresponding field.
                $msg = "Argument provided to $attribute.if ($val1) has a " .
                "falsy value.";
            }
        // If atleast two arguments were passed to the validation rule, evaluate
        // the result of expression provided.
        // If no operator is provided, the expression evaluator defaults to
        // comparing whether the two values are equal.
        } else {
            // If the expression evaluates to true.
            if ($this->evaluateExpression($val1, $val2, $operator)) {
                // If an additional validation rule is provided in the case of
                // the expression evaluating to true, validate the additional
                // rule.
                if (!empty($ifRule)) {
                    // Validate the additional rule.
                    // If the additional rule fails validation, don't worry
                    // about associating a validation message with the field
                    // since it should be handled by the validation rule
                    // evaluated.
                    $rval = $this->validateAttribute($attribute, $ifRule);
                // Otherwise, if no additional rule is provided.
                } else {
                    // Set the return value to true to signify that the field
                    // passed validation since the initial expression evaluated
                    // to true.
                    $rval = true;
                }
            // If the expression evaluates to false.
            } else {
                // If an additional validation rule is provided in the case of
                // the expression evaluating to false, validate the additional
                // rule.
                if (!empty($elseRule)) {
                    // Validate the additional rule.
                    // If the additional rule fails validation, don't worry
                    // about associating a validation message with the field
                    // since it should be handled by the validation rule
                    // evaluated.
                    $rval = $this->validateAttribute($attribute, $elseRule);
                // Otherwise, if no additional rule is provided.
                } elseif (empty($ifRule)) {
                    // Set the return value to false to signify that the field
                    // failed validation since the initial expression evaluated
                    // to false and no other validation rules were provided.
                    $rval = false;
                    // Associate an error message with the corresponding field.
                    $msg = "If expression evaluated to false: " .
                        "$val1 $operator $val2.";
                // Else, if an 'if' validation rule was provided, but not
                // reached.
                } else {
                    // Set the return value to true to signify the field passed
                    // validation, since an if validation rule was provided and
                    // the expression was only being used to determine whether
                    // this rule should be applied.
                    $rval = true;
                }
            }
        }

        // Handle error message for validation rule if one has been set.
        if (!empty($msg)) {
            $msg = ["$attribute.if" => $msg];
            $this->setCustomMessages($msg);
        }

        // Return result of validation rule.
        return $rval;
    }

    /**
     * Perform comparison of two values using provided operator.
     *
     * @param mixed $val1
     * @param mixed $val2
     * @param string $operator
     * @return bool
     */
    protected function evaluateExpression(
        $val1,
        $val2,
        string $operator = '=='
    ): bool {
        switch ($operator) {
            case '<':
                return $val1 < $val2;
            case '<=':
                return $val1 <= $val2;
            case '>':
                return $val1 > $val2;
            case '>=':
                return $val1 >= $val2;
            case '!=':
            case '<>':
                return $val1 != $val2;
            case '===':
                return $val1 === $val2;
            case '==':
            case '=':
            default:
                return $val1 == $val2;
        }
    }
}

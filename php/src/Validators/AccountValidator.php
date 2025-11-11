<?php

namespace AccountingSystem\Validators;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class AccountValidator
{
    public function validateCreate(array $data): ValidationResult
    {
        $validator = v::key('account_code', v::stringType()->notEmpty()->length(1, 20))
                     ->key('account_name', v::stringType()->notEmpty()->length(1, 255))
                     ->key('account_type', v::in(['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']))
                     ->key('opening_balance', v::number())
                     ->key('is_contra', v::boolType())
                     ->key('is_active', v::boolType())
                     ->key('parent_account_id', v::optional(v::intVal()->positive()))
                     ->key('description', v::optional(v::stringType()->length(0, 1000)))
                     ->key('tax_rate', v::optional(v::number()->between(0, 100)));

        return $this->validate($validator, $data);
    }

    public function validateUpdate(array $data): ValidationResult
    {
        $validator = v::key('account_code', v::optional(v::stringType()->notEmpty()->length(1, 20)))
                     ->key('account_name', v::optional(v::stringType()->notEmpty()->length(1, 255)))
                     ->key('account_type', v::optional(v::in(['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'])))
                     ->key('is_contra', v::optional(v::boolType()))
                     ->key('is_active', v::optional(v::boolType()))
                     ->key('parent_account_id', v::optional(v::intVal()->positive()))
                     ->key('description', v::optional(v::stringType()->length(0, 1000)))
                     ->key('tax_rate', v::optional(v::number()->between(0, 100)));

        return $this->validate($validator, $data);
    }

    private function validate(v $validator, array $data): ValidationResult
    {
        try {
            $validator->assert($data);
            return new ValidationResult(true, []);
        } catch (NestedValidationException $e) {
            return new ValidationResult(false, $e->getMessages());
        }
    }
}
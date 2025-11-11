<?php

namespace AccountingSystem\Validators;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class AuthValidator
{
    public function validateLogin(array $data): ValidationResult
    {
        $validator = v::key('username', v::stringType()->notEmpty())
                     ->key('password', v::stringType()->notEmpty());

        return $this->validate($validator, $data);
    }

    public function validateRegister(array $data): ValidationResult
    {
        $validator = v::key('username', v::stringType()->length(3, 50))
                     ->key('email', v::email())
                     ->key('password', v::stringType()->length(8, 255))
                     ->key('first_name', v::stringType()->notEmpty())
                     ->key('last_name', v::stringType()->notEmpty())
                     ->key('company_id', v::intVal()->positive());

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

class ValidationResult
{
    private bool $isValid;
    private array $errors;

    public function __construct(bool $isValid, array $errors)
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
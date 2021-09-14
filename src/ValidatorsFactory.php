<?php

declare(strict_types=1);

namespace N1215\OpenApiValidation\Laravel;

use N1215\OpenApiValidation\HttpFoundation\ValidatorBuilder;
use N1215\OpenApiValidation\HttpFoundation\Validators;

class ValidatorsFactory implements ValidatorsFactoryInterface
{
    protected ValidatorBuilder $validatorBuilder;

    public function __construct(ValidatorBuilder $validatorBuilder)
    {
        $this->validatorBuilder = $validatorBuilder;
    }

    public function make(): Validators
    {
        return $this->validatorBuilder->getValidators();
    }
}

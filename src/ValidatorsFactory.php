<?php

declare(strict_types=1);

namespace N1215\OpenApiValidation\Laravel;

use N1215\OpenApiValidation\HttpFoundation\ValidatorBuilder;
use N1215\OpenApiValidation\HttpFoundation\Validators;

class ValidatorsFactory implements ValidatorsFactoryInterface
{
    public function __construct(
        protected readonly ValidatorBuilder $validatorBuilder
    ) {
    }

    public function make(): Validators
    {
        return $this->validatorBuilder->getValidators();
    }
}

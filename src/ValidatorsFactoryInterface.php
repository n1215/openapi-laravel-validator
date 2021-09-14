<?php

declare(strict_types=1);

namespace N1215\OpenApiValidation\Laravel;

use N1215\OpenApiValidation\HttpFoundation\Validators;

interface ValidatorsFactoryInterface
{
    public function make(): Validators;
}

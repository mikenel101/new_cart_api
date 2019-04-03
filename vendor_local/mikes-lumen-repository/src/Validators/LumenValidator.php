<?php

namespace MikesLumenRepository\Validators;

use Prettus\Validator\LaravelValidator;

class LumenValidator extends LaravelValidator
{
    public function __construct()
    {
        $this->validator = app('validator');
    }
}

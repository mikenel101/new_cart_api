<?php
namespace MikesLumenApi\Exceptions;

use Prettus\Validator\Exceptions\ValidatorException as BaseValidatorException;
use Illuminate\Support\MessageBag;

class ValidatorException extends BaseValidatorException
{
    public function __construct(array $messages)
    {
        $messageBag = new MessageBag();
        foreach ($messages as $key => $value) {
            $messageBag->add($key, $value);
        }
        parent::__construct($messageBag);
    }
}

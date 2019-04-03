<?php
namespace MikesLumenApi\Exceptions;

class NotImplementedException extends AppException
{

    public function __construct($message = 'Not implemented')
    {
        parent::__construct($message, 'not_implemented');
    }
}

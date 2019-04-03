<?php
namespace MikesLumenApi\Exceptions;

class AppException extends \Exception
{

    public function __construct($message = "", $code = "")
    {
        parent::__construct($message);
        $this->errorCode = $code;
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->errorCode}]: {$this->message}\n" . $this->getTraceAsString();
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}

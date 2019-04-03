<?php
namespace MikesLumenApi\Exceptions;

class RequestException extends \Exception
{
    public const CODE_DUPLICATED_ENTRY = 'request.duplicated_entry';

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

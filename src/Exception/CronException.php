<?php
namespace App\Exception;

use Exception;
use Throwable;

class CronException extends Exception
{
    /**
     * @param string $message
     * @param string|null $cronName
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = "",
        ?string $cronName = NULL,
        int $code = 0,
        ?Throwable $previous = NULL
    )
    {
        $message = ($cronName !== NULL) ? sprintf("[%s]: %s", $cronName, $message) : $message;
        parent::__construct($message, $code, $previous);
    }
}
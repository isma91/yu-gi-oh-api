<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\VarDump as VarDumpService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Logger
{
    private int $limitLogFileInMo = 10;
    public const ERROR = "error";
    public const WARNING = "warning";
    public const INFO = "info";
    public const EMAIL = "info_email";
    public const DEBUG = "debug";
    private ?Throwable $exception = NULL;
    private string $level = self::ERROR;
    private string $message = "";
    private bool $isCron = FALSE;
    private ?string $filePath = NULL;
    private ParameterBagInterface $param;
    private Filesystem $filesystem;
    private VarDumpService $varDumpService;

    public function __construct(
        ParameterBagInterface $param,
        Filesystem $filesystem,
        VarDumpService $varDumpService
    )
    {
        $this->param = $param;
        $this->filesystem = $filesystem;
        $this->varDumpService = $varDumpService;
    }

    /**
     * @param Throwable|null $exception
     * @return $this
     */
    public function setException(?Throwable $exception = NULL): Logger
    {
        $this->exception = $exception;
        return $this;
    }

    /**
     * @param string $level
     * @return $this
     */
    public function setLevel(string $level): Logger
    {
        $levelArray = [
            self::ERROR,
            self::WARNING,
            self::DEBUG,
            self::INFO,
            self::EMAIL,
        ];
        if (in_array($level, $levelArray, TRUE) === TRUE) {
            $this->level = $level;
        }
        return $this;
    }

    /**
     * @param bool $isCron
     * @return Logger
     */
    public function setIsCron(bool $isCron): Logger
    {
        $this->isCron = $isCron;
        return $this;
    }

    /**
     * Create an empty file with DATE_IS_CRON_LEVEL.txt as name
     * if the file does not exist
     * @return bool TRUE when there is no error, FALSE when we catch an Exception
     */
    public function createFileIfNotExist(): bool
    {
        try {
            $logDir = $this->param->get("LOG_DIR");
            $fileName = date("Y-m-d");
            if ($this->isCron === TRUE) {
                $fileName .= "_cron";
            }
            $fileName .= sprintf("_%s.txt", $this->level);
            $filePath = $logDir . DIRECTORY_SEPARATOR . $fileName;
            $this->filePath = $filePath;
            if ($this->filesystem->exists($filePath) === FALSE) {
                $this->filesystem->dumpFile($filePath, "");
            }
        } catch (IOException|Exception $IOException) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Parse the Exception to have all information to add in log file
     * @return string
     */
    public function parseException():string
    {
        $exception = $this->exception;
        if ($exception === NULL) {
            return "";
        }
        $message = $exception->getMessage();
        $code = $exception->getCode();
        $class = get_class($exception);
        $message = sprintf(" - ExceptionType: %s\n   Message: %s\n   Code: %d\n\n", $class, $message, $code);
        $classByPassTrace = [NotFoundHttpException::class, MethodNotAllowedHttpException::class];
        //Just need the message for CRON Exception and some Route Exception
        if ($this->isCron === TRUE || in_array($class, $classByPassTrace, TRUE) === TRUE) {
            return $message;
        }
        $exceptionTrace = $exception->getTrace();
        $countBacktrace = count($exceptionTrace);
        //Symfony create a lot of trace so, we skip the basic one who don't have information we need
        if ($countBacktrace > 6) {
            array_splice($exceptionTrace, -3);
        }
        //Reverse to have the top Exception first then we go deeper
        $exceptionTrace = array_reverse($exceptionTrace);
        $message .= $this->parseTraceArray($exceptionTrace);
        return $message;
    }

    /**
     * @param bool $isFromInfo We can use log for info purpose so, we skip less
     * @return string
     */
    public function parseDebugBacktrace(bool $isFromInfo = FALSE): string
    {
        $backtrace = debug_backtrace(0);
        $countBacktrace = count($backtrace);
        //Symfony create a lot of Trace so, we skip some of them
        if ($countBacktrace >= 6) {
            //Remove less when it's just an info log
            $length = $isFromInfo === FALSE ? 3 : 1;
            array_splice($backtrace, 0, $length);
            array_splice($backtrace, -3);
        }
        return $this->parseTraceArray($backtrace);
    }

    /**
     * Use it when we only need to get Exception and no message,
     * like when we catch Exception for some specific function
     * @return void
     */
    public function addErrorExceptionOrTrace(): void
    {
        $this->addLog(NULL, TRUE);
    }

    /**
     * @param string|null $message
     * @param bool $addBacktrace
     * @return void
     */
    public function addLog(?string $message = NULL, bool $addBacktrace = FALSE): void
    {
        $this->message = $this->addDateTimeToMessage("BEGIN LOG\n");
        if ($this->createFileIfNotExist() === TRUE) {
            if (empty($message) === FALSE) {
                $this->message .= sprintf("%s\n", $message);
            }
            if ($this->exception !== NULL) {
                $this->message .= $this->parseException();
            }
            if ($addBacktrace === TRUE) {
                $this->message .= $this->parseDebugBacktrace();
            }
            $this->message .= $this->addDateTimeToMessage("END LOG\n");
            $this->writeLog();
        }
    }

    /**
     * @param string $message
     * @return string
     */
    public function addDateTimeToMessage(string $message): string
    {
        return "[" .date("Y-m-d H:i:s") . "]: " . $message;
    }

    /**
     * Parse the Backtrace to display properly in the log file
     * @param array $traceArray
     * @return string
     */
    public function parseTraceArray(array $traceArray): string
    {
        $message = "";
        foreach ($traceArray as $trace) {
            [
                "line" => $line,
                "function" => $function,
            ] = $trace;
            $type = "";
            $class = "none";
            if (isset($trace["class"]) === true) {
                $class = $trace["class"];
            }
            if (isset($trace["type"]) === TRUE) {
                $type = $trace["type"];
            }
            $message .= sprintf(" - Class|Type|Function: %s%s%s\n   Line: %d\n", $class, $type, $function, $line);
            $argMessage = "";
            if (isset($trace["args"]) === TRUE) {
                $args = $trace["args"];
            } else {
                $args = [];
            }
            if (empty($args) === FALSE) {
                foreach ($args as $arg) {
                    $argMessage .= $this->varDumpService->varDump($arg);
                }
            }
            if ($argMessage !== "") {
                $message .= sprintf("Args: \n%s\n", $argMessage);
            }
        }
        return $message;
    }

    /**
     * Write the message at the beginning of the File
     * It's like a append to the beginning of file without replace the existing content.
     * Create a temp file with the new message to add,
     * then we get the context of the current log file to append in the temp file.
     * After that we can remove the current log file and move the temp file as the new current log file.
     * @return void
     */
    public function writeLog(): void
    {
        $logFilePath = $this->filePath;
        if ($logFilePath !== NULL) {
            $logFileSize = filesize($logFilePath) / 1000000;
            if ($logFileSize < $this->limitLogFileInMo) {
                [
                    "dirname" => $logFileDir,
                    "filename" => $logFileFilename,
                    "extension" => $logFileExtension
                ] = pathinfo($logFilePath);
                $tempFilePath = sprintf(
                    "%s%s%s.%s",
                    $logFileDir,
                    DIRECTORY_SEPARATOR,
                    $logFileFilename . "_bis",
                    $logFileExtension
                );
                $streamContext = stream_context_create();
                // get content of current logfile as context
                // to avoid load the full file in memory
                $logFileContext = fopen($logFilePath, 'rb', 1, $streamContext);
                $this->filesystem->dumpFile($tempFilePath, $this->message);
                $this->filesystem->appendToFile($tempFilePath, $logFileContext);
                fclose($logFileContext);
                $this->filesystem->remove($logFilePath);
                $this->filesystem->rename($tempFilePath, $logFilePath);
            }
        }
    }

    /**
     * Write Info log, used from controller to get all request send to the API
     * @return void
     */
    public function writeInfoFromDebugBacktrace() : void
    {
        $this->message = $this->addDateTimeToMessage("BEGIN LOG\n");
        if ($this->createFileIfNotExist() === TRUE) {
            $this->message .= $this->parseDebugBacktrace(TRUE);
            $this->message .= $this->addDateTimeToMessage("END LOG\n");
            $this->writeLog();
        }
    }
}
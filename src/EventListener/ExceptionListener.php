<?php
namespace App\EventListener;

use App\Service\Logger as LoggerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ExceptionListener
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    public function addToLog(Throwable $exception): void
    {
        $this->loggerService->setException($exception)
            ->setLevel(LoggerService::ERROR)
            ->setIsCron(FALSE)
            ->addErrorExceptionOrTrace();
    }

    /**
     * @param ExceptionEvent $event
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $jsonResponse = new JsonResponse();
        $errorMsg = "An error has occurred, please try your action again later.";
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception instanceof NotFoundHttpException || $exception instanceof MethodNotAllowedHttpException) {
            $errorMsg = "Route not found, go to /swagger to see the full documentation";
            $statusCode = Response::HTTP_NOT_FOUND;
            $event->setResponse($jsonResponse);
        } else if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            if ($statusCode === Response::HTTP_UNAUTHORIZED) {
                $errorMsg = "An authentication is mandatory to access to this action.";
            } else {
                $this->addToLog($exception);
            }
        } else {
            $this->addToLog($exception);
        }
        $jsonResponse->setData(["error" => $errorMsg, "data" => NULL]);
        $jsonResponse->setStatusCode($statusCode);
        $event->setResponse($jsonResponse);
    }
}
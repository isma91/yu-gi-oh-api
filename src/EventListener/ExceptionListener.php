<?php
namespace App\EventListener;

use App\Service\Logger as LoggerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    /**
     * @param ExceptionEvent $event
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundHttpException || $exception instanceof MethodNotAllowedHttpException) {
            $jsonResponse = new JsonResponse(
                [
                    "error" => "Route not found, go to /swagger to see the full documentation",
                    "data" => NULL
                ],
                Response::HTTP_NOT_FOUND
            );
            $event->setResponse($jsonResponse);
        } else {
            $this->loggerService->setException($exception)
                ->setLevel(LoggerService::ERROR)
                ->setIsCron(FALSE)
                ->addErrorExceptionOrTrace();
            $response = new Response();
            $response->setContent("An error has occurred, please try your action again later.");
            if ($exception instanceof HttpExceptionInterface) {
                $response->setStatusCode($exception->getStatusCode());
                $response->headers->replace($exception->getHeaders());
            } else {
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $event->setResponse($response);
        }
    }
}
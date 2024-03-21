<?php
namespace App\EventListener;

use App\Service\Logger as LoggerService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    private LoggerService $loggerService;
    private ParameterBagInterface $param;

    public function __construct(LoggerService $loggerService, ParameterBagInterface $param)
    {
        $this->loggerService = $loggerService;
        $this->param = $param;
    }

    /**
     * @param ExceptionEvent $event
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        if ($this->param->get('APP_ENV') === 'prod') {
            $exception = $event->getThrowable();
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
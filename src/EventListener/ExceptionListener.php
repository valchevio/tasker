<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    private $logger;
    private $environment;

    public function __construct(LoggerInterface $logger, string $environment)
    {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $response = [
            'code' => 500,
            'error' => 'An unexpected error occurred',
        ];

        if ($exception instanceof HttpExceptionInterface) {
            $response['code'] = $exception->getStatusCode();
            $response['error'] = $exception->getMessage();
        }

        if ($exception instanceof NotFoundHttpException) {
            $response['code'] = 404;
            $response['error'] = 'No route found';
        }

        $this->logger->error(sprintf(
            'Exception caught: %s (Code: %s) in %s at line %s',
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine()
        ));

        if ($this->environment === 'dev') {
            $response['details'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        $jsonResponse = new JsonResponse($response, 200);

        $event->setResponse($jsonResponse);
    }
}

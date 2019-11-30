<?php declare(strict_types=1);

namespace App\Infrastructure\Symfony\ExceptionListener;

use Assert\LazyAssertionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LazyAssertionExceptionListener
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onKernelException(ExceptionEvent $event): ExceptionEvent
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof LazyAssertionException) {
            return $event;
        }

        $errors = [];

        foreach ($exception->getErrorExceptions() as $errorException) {
            $errors[$errorException->getPropertyPath()] = $this->translator->trans($errorException->getMessage());
        }

        $response = new JsonResponse([
            'errors' => $errors,
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

        return $event->setResponse($response);
    }
}

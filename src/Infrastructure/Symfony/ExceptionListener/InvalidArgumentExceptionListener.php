<?php declare(strict_types=1);

namespace App\Infrastructure\Symfony\ExceptionListener;

use Assert\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InvalidArgumentExceptionListener
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof InvalidArgumentException) {
            return $event;
        }

        $response = new JsonResponse([
            'errors' => [
                $exception->getPropertyPath() => $this->translator->trans($exception->getMessage()),
            ],
        ], JsonResponse::HTTP_BAD_REQUEST);

        return $event->setResponse($response);
    }
}

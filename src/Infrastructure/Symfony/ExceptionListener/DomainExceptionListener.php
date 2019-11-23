<?php declare(strict_types=1);

namespace App\Infrastructure\Symfony\ExceptionListener;

use Assert\LazyAssertionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DomainExceptionListener
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

        if (!$exception instanceof \DomainException) {
            return $event;
        }

        $response = new JsonResponse([
            'error' => $this->translator->trans($exception->getMessage()),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

        return $event->setResponse($response);
    }
}

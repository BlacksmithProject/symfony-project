<?php declare(strict_types=1);

namespace App\Infrastructure\Symfony;

use App\Infrastructure\Symfony\Exception\AuthenticationException;
use App\Infrastructure\Symfony\Exception\DomainException;
use Assert\InvalidArgumentException;
use Assert\LazyAssertionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ExceptionListener
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

        $response = $this->buildResponseFor($exception);

        if ($response !== null) {
            $event->setResponse($response);
        }

        return $event;
    }

    private function buildResponseFor(\Throwable $exception): ?JsonResponse
    {
        switch (get_class($exception)) {
            case DomainException::class:
                return new JsonResponse([
                    'error' => $this->translator->trans($exception->getMessage()),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                break;
            case AuthenticationException::class:
                return new JsonResponse([
                    'error' => $this->translator->trans($exception->getMessage()),
                ], JsonResponse::HTTP_UNAUTHORIZED);
                break;
            case InvalidArgumentException::class:
                return new JsonResponse([
                    'error' => [
                        $exception->getPropertyPath() => $this->translator->trans($exception->getMessage()),
                    ],
                ], JsonResponse::HTTP_BAD_REQUEST);
                break;
            case LazyAssertionException::class:
                $errors = [];

                /** @var InvalidArgumentException  $errorException */
                foreach ($exception->getErrorExceptions() as $errorException) {
                    $errors[$errorException->getPropertyPath()] = $this->translator->trans($errorException->getMessage());
                }

                return new JsonResponse([
                    'errors' => $errors,
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                break;
            default:
                return null;
        }
    }
}

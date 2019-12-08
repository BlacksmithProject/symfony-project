<?php declare(strict_types=1);

namespace App\UserAccount;

use App\Infrastructure\Symfony\Exception\AuthenticationException as AppAuthenticationException;
use App\UserAccount\Token\TokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApiTokenAuthenticator extends AbstractGuardAuthenticator
{
    /** @var TokenRepository */
    private $tokenRepository;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        TokenRepository $tokenRepository,
        TranslatorInterface $translator
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->translator = $translator;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse([
            'error' => 'missing.auth.header',
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function supports(Request $request)
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    public function getCredentials(Request $request)
    {
        return ['api_token' => $request->headers->get('X-AUTH-TOKEN')];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = $this->tokenRepository->findByValue($credentials['api_token']);

        if (!$token || $token->isExpired()) {
            throw new AppAuthenticationException('users.invalid_token');
        }

        return $token->user();
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse([
            'error' => $this->translator->trans('users.invalid_token'),
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerkey)
    {
        return null;
    }

    public function supportsRememberMe()
    {
        return false;
    }
}

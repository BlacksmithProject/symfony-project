<?php declare(strict_types=1);

namespace App\UserAccount;

use App\Infrastructure\Symfony\Exception\AuthenticationException;
use App\Infrastructure\Symfony\Exception\DomainException;
use App\UserAccount\Token\AuthenticationTokenType;
use App\UserAccount\Token\Token;
use Assert\Assert;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class AuthenticationController
{
    /** @var UserRepository */
    private $userRepository;
    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;
    /** @var SerializerInterface */
    private $serializer;
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    public function __invoke(Request $request): Response
    {
        $decodedAuthorization = $this->getDecodedAuthorization($request);
        $email = $decodedAuthorization[0];
        $password = $decodedAuthorization[1];

        $this->validateRequest($email, $password);

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new DomainException('users.not_found');
        }

        if (!$this->passwordEncoder->isPasswordValid($user, $password)) {
            throw new AuthenticationException('users.invalid_credentials');
        }

        if ($user->authenticationToken()->isExpired()) {
            $this->entityManager->remove($user->authenticationToken());

            $authenticationToken = Token::generateFor($user, new AuthenticationTokenType());
            $this->entityManager->persist($authenticationToken);

            $user->setAuthenticationToken($authenticationToken);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return new Response(
            $this->serializer->serialize($user, 'json', ['groups' => ['user_private']]),
            Response::HTTP_OK
        );
    }

    /**
     * @return array<int, string>
     */
    private function getDecodedAuthorization(Request $request): array
    {
        Assert::that($request->headers->get('Authorization'), null, 'authenticationHeader')
            ->notNull('authorization.basic.missing_header');

        $encodedAuthorization = explode(' ', $request->headers->get('Authorization'));

        if (!$encodedAuthorization || !isset($encodedAuthorization[1])) {
            throw new \RuntimeException('failed to explode authorization headers');
        }

        $decodedAuthorization = explode(':', (string) base64_decode($encodedAuthorization[1]));

        if (!$decodedAuthorization) {
            throw new \RuntimeException('failed to explode encoded authorization');
        }

        return $decodedAuthorization;
    }

    private function validateRequest(?string $email, ?string $password): void
    {
        Assert::that($email, null, 'email')->notNull('data_control.users.is_null.email');
        Assert::that($password, null, 'password')->notNull('data_control.users.is_null.password');

        Assert::lazy()
            ->that($email, 'email')
            ->notBlank('data_control.users.is_blank.email')
            ->email('data_control.users.is_email.email')

            ->that($password, 'password')
            ->string('data_control.users.is_not_string.password')
            ->notBlank('data_control.users.is_blank.password')

            ->verifyNow();
    }
}

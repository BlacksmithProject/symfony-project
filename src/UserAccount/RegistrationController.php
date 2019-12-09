<?php declare(strict_types=1);

namespace App\UserAccount;

use App\Infrastructure\Symfony\Exception\DomainException;
use App\UserAccount\Token\AuthenticationTokenType;
use App\UserAccount\Token\Token;
use Assert\Assert;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class RegistrationController
{
    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function __invoke(Request $request): Response
    {
        $this->validateRequest($request);

        $user = User::register(
            $request->request->get('email'),
            $request->request->get('password'),
            $request->request->get('username'),
            $this->passwordEncoder
        );
        $this->entityManager->persist($user);

        $authenticationToken = Token::generateFor($user, new AuthenticationTokenType());
        $this->entityManager->persist($authenticationToken);

        $user->setAuthenticationToken($authenticationToken);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new DomainException('users.already_used');
        }

        return new Response(
            $this->serializer->serialize($user, 'json', ['groups' => 'user_private']),
            Response::HTTP_CREATED
        );
    }

    private function validateRequest(Request $request): void
    {
        Assert::that($request->request->get('email'), null, 'email')->notNull('data_control.users.is_null.email');
        Assert::that($request->request->get('password'), null, 'password')->notNull('data_control.users.is_null.password');
        Assert::that($request->request->get('username'), null, 'username')->notNull('data_control.users.is_null.username');

        Assert::lazy()
            ->that($request->request->get('email'), 'email')
            ->notBlank('data_control.users.is_blank.email')
            ->email('data_control.users.is_email.email')

            ->that($request->request->get('password'), 'password')
            ->string('data_control.users.is_not_string.password')
            ->notBlank('data_control.users.is_blank.password')

            ->that($request->request->get('username'), 'username')
            ->string('data_control.users.is_not_string.username')
            ->notBlank('data_control.users.is_blank.username')

            ->verifyNow();
    }
}

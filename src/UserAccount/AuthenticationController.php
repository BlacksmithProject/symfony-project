<?php declare(strict_types=1);

namespace App\UserAccount;

use App\Infrastructure\Symfony\Controller;
use App\Infrastructure\Symfony\ExceptionListener\AuthenticationException;
use App\Infrastructure\Symfony\ExceptionListener\DomainException;
use Assert\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class AuthenticationController implements Controller
{
    /** @var UserRepository */
    private $userRepository;
    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        SerializerInterface $serializer
    ) {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->serializer = $serializer;
    }

    public function __invoke(Request $request)
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

        return new Response(
            $this->serializer->serialize($user, static::JSON, ['groups' => ['user_private', 'user_public']]),
            Response::HTTP_OK
        );
    }

    private function getDecodedAuthorization(Request $request)
    {
        Assert::that($request->headers->get('Authorization'), null, 'authenticationHeader')
            ->notNull('authorization.basic.missing_header');

        $encodedAuthorization = explode(' ', $request->headers->get('Authorization'));

        return explode(':', (string) base64_decode($encodedAuthorization[1]));
    }

    private function validateRequest(?string $email, ?string $password): void
    {
        Assert::that($email, null, 'email')->notNull('data_control.is_null.email');
        Assert::that($password, null, 'password')->notNull('data_control.is_null.password');

        Assert::lazy()
            ->that($email, 'email')
            ->notBlank('data_control.is_blank.email')
            ->email('data_control.is_email.email')

            ->that($password, 'password')
            ->string('data_control.is_not_string.password')
            ->notBlank('data_control.is_blank.password')

            ->verifyNow();
    }
}

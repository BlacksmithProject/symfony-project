<?php declare(strict_types=1);

namespace App\UserAccount;

use App\UserAccount\Token\Token;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\UserAccount\UserRepository")
 * @ORM\Table(name="users")
 */
final class User implements UserInterface
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     *
     * @Groups({"owner", "all"})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Groups({"owner"})
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Groups({"owner", "all"})
     */
    private $name;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @var PersistentCollection
     * @ORM\OneToMany(targetEntity="App\UserAccount\Token\Token", mappedBy="user")
     *
     * @Groups({"owner"})
     */
    private $tokens;

    public static function register(
        string $email,
        string $password,
        string $username,
        Token $apiToken,
        UserPasswordEncoderInterface $passwordEncoder
    ): self {
        $user = new self();
        $now = new \DateTimeImmutable();

        $user->email = $email;
        $user->password = $passwordEncoder->encodePassword($user, $password);
        $user->name = $username;
        $user->createdAt = $now;
        $user->updatedAt = $now;

        $user->tokens[] = $apiToken;

        return $user;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function tokens(): PersistentCollection
    {
        return $this->tokens;
    }

    public function addToken(Token $token): User
    {
        $this->tokens[(string) $token->type()] = $token;

        return $this;
    }

    public function getRoles()
    {
        return [];
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->name;
    }

    public function eraseCredentials()
    {
    }
}

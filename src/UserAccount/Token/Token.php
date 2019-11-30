<?php declare(strict_types=1);

namespace App\UserAccount\Token;

use App\UserAccount\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tokens")
 */
class Token
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @Groups({"user_private"})
     */
    private $value;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"user_private"})
     */
    private $expireAt;

    /**
     * @var TokenType
     * @ORM\Column(type="string", length=255)
     * @Groups({"user_private"})
     */
    private $type;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\UserAccount\User", inversedBy="authenticationToken")
     */
    private $user;

    private function __construct()
    {
    }

    public static function generateFor(
        User $user,
        TokenType $tokenType,
        \DateInterval $duration = null
    ): self {
        $token = new self();

        $token->user = $user;
        $token->value = static::generateTokenValue();
        $token->expireAt = (new \DateTimeImmutable())->add($duration ?? new \DateInterval('P15D'));
        $token->type = $tokenType;

        return $token;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function expireAt(): \DateTimeImmutable
    {
        return $this->expireAt;
    }

    public function type(): string
    {
        return (string) $this->type;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function isExpired(): bool
    {
        return $this->expireAt < new \DateTimeImmutable();
    }

    private static function generateTokenValue(int $length = 12): string
    {
        try {
            $random = random_bytes($length);

            return base64_encode(sprintf('%1$s%2$s', $random, Uuid::uuid4()->toString()));
        } catch (\Exception $e) {
            throw new \RuntimeException('failed to generated Uuid');
        }
    }
}

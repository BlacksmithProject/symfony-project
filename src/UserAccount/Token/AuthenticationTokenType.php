<?php declare(strict_types=1);

namespace App\UserAccount\Token;

final class AuthenticationTokenType implements TokenType
{
    private const TYPE = 'authentication';

    public function __toString(): string
    {
        return static::TYPE;
    }
}

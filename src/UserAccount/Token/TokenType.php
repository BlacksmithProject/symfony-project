<?php declare(strict_types=1);

namespace App\UserAccount\Token;

interface TokenType
{
    public function __toString(): string;
}

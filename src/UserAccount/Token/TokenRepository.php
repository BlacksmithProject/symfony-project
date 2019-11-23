<?php declare(strict_types=1);

namespace App\UserAccount\Token;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

final class TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    public function findByValue(string $tokenValue): ?Token
    {
        return $this->createQueryBuilder('token')
            ->select('token')
            ->where('token.value = :value')
            ->setParameter('value', $tokenValue)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

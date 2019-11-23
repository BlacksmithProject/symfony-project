<?php declare(strict_types=1);

namespace App\UserAccount;

use App\UserAccount\Token\AuthenticationTokenType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('user')
            ->select('user')
            ->where('user.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

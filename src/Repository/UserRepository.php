<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function saveUser(User $entityUser, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entityUser);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeUser(User $entityUser, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entityUser);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

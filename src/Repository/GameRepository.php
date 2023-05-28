<?php
namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function saveGame(Game $entityGame, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entityGame);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeGame(Game $entityGame, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entityGame);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

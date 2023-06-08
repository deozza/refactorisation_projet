<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 *
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * @param User $player
     * @param int $gameId
     * 
     * @return Game|null
     */
    public function getGameByEitherPlayer(User $player, int $gameId): Game | null
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('p.id = :gameId')
            ->andWhere('p.playerLeft = :player OR p.playerRight = :player');
            
        $qb->setParameters([
            'gameId' => $gameId,
            'player' => $player
        ]);

        $query = $qb->getQuery();

        return $query->setMaxResults(1)->getOneOrNullResult();
   }
}
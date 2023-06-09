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
            ->andWhere('g.id = :gameId')
            ->andWhere('g.playerLeft = :player OR g.playerRight = :player');
            
        $qb->setParameters([
            'gameId' => $gameId,
            'player' => $player
        ]);

        $query = $qb->getQuery();

        return $query->setMaxResults(1)->getOneOrNullResult();
   }

       /**
     * @param Game|null $game
     * 
     * @return void
     */
    public function save(?Game $game = null)
    {
        if(empty($game) === false){
            $this->getEntityManager()->persist($game);
        }

        $this->getEntityManager()->flush();
    }
   
    /**
     * @param Game $game
     * 
     * @return void
     */
    public function delete(Game $game): void
    {
        $this->getEntityManager()->remove($game);
        $this->save();
    }
}
<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;

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

    public function save(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param int $id
     * @param User $player
     * @return Game|null
     */
    public function findGameByIdAndPlayer(int $id, User $player): Game | null
    {
        $qb = $this->createQueryBuilder('g');
        $qb->where('g.id = :id')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('g.playerLeft', ':player'),
                    $qb->expr()->eq('g.playerRight', ':player')
                )
            )
            ->setParameter('id', $id)
            ->setParameter('player', $player);
        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            // Should never happen
            throw new LogicException("Non unique result for game id $id and player id {$player->getId()}");
        }
    }
}

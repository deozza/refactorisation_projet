<?php

namespace App\Controller\Game;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GetGameInfoController extends AbstractController
{
    #[Route('/game/{identifier}', name: 'fetch_game', methods:['GET'])]
    /**
     * Fetches game information by identifier.
     *
     * @param EntityManagerInterface $entityManager
     * @param mixed $identifier
     *
     * @return JsonResponse
     */
    public function getGameInfo(EntityManagerInterface $entityManager, $identifier): JsonResponse
    {
        $party = $this->findGameById($entityManager, $identifier);

        if ($party !== null) {
            return $this->json($party);
        }
        
        return new JsonResponse('Game not found', 404);
    }

    /**
     * Finds a game by its identifier.
     *
     * @param EntityManagerInterface $entityManager
     * @param mixed $identifier
     *
     * @return Game|null
     */
    private function findGameById(EntityManagerInterface $entityManager, $identifier): ?Game
    {
        if (!$this->isValidIdentifier($identifier)) {
            return null;
        }

        return $entityManager->getRepository(Game::class)->find($identifier);
    }

    /**
     * Checks if the given identifier is valid (numeric).
     *
     * @param mixed $identifier
     *
     * @return bool
     */
    private function isValidIdentifier($identifier): bool
    {
        return ctype_digit($identifier);
    }
}
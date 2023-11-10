<?php

namespace App\Controller\Game;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GetGameInfoController extends AbstractController
{
    #[Route('/game/{identifiant}', name: 'fetch_game', methods:['GET'])]
    /**
     * Fetches game information by identifier.
     *
     * @param EntityManagerInterface $entityManager
     * @param mixed $identifiant
     *
     * @return JsonResponse
     */
    public function getGameInfo(EntityManagerInterface $entityManager, $identifiant): JsonResponse
    {
        $party = $this->findGameById($entityManager, $identifiant);

        if ($party !== null) {
            return $this->json($party);
        }

        return new JsonResponse('Game not found', 404);
    }

    /**
     * Finds a game by its identifier.
     *
     * @param EntityManagerInterface $entityManager
     * @param mixed $identifiant
     *
     * @return Game|null
     */
    private function findGameById(EntityManagerInterface $entityManager, $identifiant): ?Game
    {
        if (!$this->isValidIdentifier($identifiant)) {
            return null;
        }

        return $entityManager->getRepository(Game::class)->find($identifiant);
    }

    /**
     * Checks if the given identifier is valid (numeric).
     *
     * @param mixed $identifiant
     *
     * @return bool
     */
    private function isValidIdentifier($identifiant): bool
    {
        return ctype_digit($identifiant);
    }
}
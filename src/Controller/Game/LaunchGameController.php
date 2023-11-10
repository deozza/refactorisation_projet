<?php

namespace App\Controller\Game;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LaunchGameController extends AbstractController
{
    #[Route('/games', name: 'create_game', methods: ['POST'])]
    /**
     * Initiates the launch of a new game based on the provided request and user information.
     *
     * @param Request $request The HTTP request containing necessary data.
     * @param EntityManagerInterface $entityManager The entity manager for persisting data.
     *
     * @return JsonResponse A JSON response containing the newly created game or an error message.
     */
    public function launchGame(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Extract user ID from request headers
        $currentUserId = $request->headers->get('X-User-Id');

        // Validate and retrieve the user
        $currentUser = $this->validateAndRetrieveUser($currentUserId, $entityManager);

        // If the user is not valid, return an error response
        if (!$currentUser) {
            return new JsonResponse('User not found', 401);
        }

        // Create a new game and set its initial state
        $newGame = new Game();
        $newGame->setState('pending');
        $newGame->setPlayerLeft($currentUser);

        // Persist the new game entity
        $entityManager->persist($newGame);
        $entityManager->flush();

        // Return the newly created game as JSON response
        return $this->json(
            $newGame,
            201,
            ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    /**
     * Validate user ID, retrieve the user from the database, and return it.
     *
     * @param string|null $currentUserId
     * @param EntityManagerInterface $entityManager
     * @return User|null
     */
    private function validateAndRetrieveUser(?string $currentUserId, EntityManagerInterface $entityManager): ?User
    {
        if ($currentUserId === null || !ctype_digit($currentUserId)) {
            return null; // Invalid user ID
        }

        return $entityManager->getRepository(User::class)->find($currentUserId);
    }
}

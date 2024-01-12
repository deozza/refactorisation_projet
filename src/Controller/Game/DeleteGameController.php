<?php

namespace App\Controller\Game;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DeleteGameController extends AbstractController
{
    #[Route('/game/{id}', name: 'annuler_game', methods:['DELETE'])]
    /**
     * Deletes a game based on the provided game ID and user ID.
     *
     * @param EntityManagerInterface $entityManager The entity manager for database interactions.
     * @param Request $request The HTTP request object containing headers and data.
     * @param int $id The ID of the game to be deleted.
     *
     * @return JsonResponse Returns a JSON response indicating the success or failure of the operation.
     */
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {
        // Retrieve the user ID from the request headers.
        $currentUserId = $request->headers->get('X-User-Id');

        // Check if the user ID is a valid numeric value.
        if (ctype_digit($currentUserId) === true) {
            // Find the user based on the retrieved ID.
            $player = $entityManager->getRepository(User::class)->find($currentUserId);

            // Check if the user exists.
            if ($player !== null) {

                // Check if the provided game ID is a valid numeric value.
                if (ctype_digit($id) === false) {
                    // Return a 404 Not Found response if the game ID is not valid.
                    return new JsonResponse('Game not found', 404);
                }

                // Attempt to find the game where the current user is the left player.
                $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerLeft' => $player]);

                // If the left player association is empty, try finding the game where the current user is the right player.
                if (empty($game)) {
                    $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerRight' => $player]);
                    // If no game is found, return a 403 Forbidden response.
                    if (empty($game)) {
                        return new JsonResponse('Game not found', 403);
                    }
                }

                // Remove the game entity from the database.
                $entityManager->remove($game);
                // Commit the changes to the database.
                $entityManager->flush();

                // Return a 204 No Content response indicating a successful deletion.
                return new JsonResponse(null, 204);

            } else {
                // Return a 401 Unauthorized response if the user is not found.
                return new JsonResponse('User not found', 401);
            }
        } else {
            // Return a 401 Unauthorized response if the user ID is not a valid numeric value.
            return new JsonResponse('User not found', 401);
        }
    }
}

<?php

namespace App\Controller\Game;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class InviteToGameController extends AbstractController
{
    #[Route('/game/{id}/add/{playerRightId}', name: 'add_user_right', methods:['PATCH'])]
    /**
     * Invites a player to join a game.
     *
     * @param Request $request The HTTP request object.
     * @param EntityManagerInterface $entityManager The entity manager for database interactions.
     * @param int $id The ID of the game.
     * @param int $playerRightId The ID of the player to be invited.
     *
     * @return JsonResponse The JSON response indicating the result of the invitation process.
     */
    public function inviteToGame(Request $request, EntityManagerInterface $entityManager, $id, $playerRightId): JsonResponse
    {
        // Retrieve the current user ID from the headers
        $currentUserId = $request->headers->get('X-User-Id');

        // Check if the current user ID is empty
        if(empty($currentUserId)){
            return new JsonResponse('User not found', 401);
        }

        // Validate IDs as integers
        if(ctype_digit($id) && ctype_digit($playerRightId) && ctype_digit($currentUserId)){
            // Find the left player (current user)
            $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);

            // Check if the left player exists
            if($playerLeft === null){
                return new JsonResponse('User not found', 401);
            }

            // Find the game
            $game = $entityManager->getRepository(Game::class)->find($id);

            // Check if the game exists
            if($game === null){
                return new JsonResponse('Game not found', 404);
            }

            // Check if the game is ongoing or finished
            if($game->getState() === 'ongoing' || $game->getState() === 'finished'){
                return new JsonResponse('Game already started', 409);
            }

            // Find the right player
            $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);

            // Check if the right player exists
            if($playerRight !== null){
                // Check if the left player is not the same as the right player
                if($playerLeft->getId() === $playerRight->getId()){
                    return new JsonResponse('You can\'t play against yourself', 409);
                }

                // Update game details and save changes
                $game->setPlayerRight($playerRight);
                $game->setState('ongoing');
                $entityManager->flush();

                // Return the updated game as JSON response
                return $this->json(
                    $game,
                    headers: ['Content-Type' => 'application/json;charset=UTF-8']
                );
            } else {
                return new JsonResponse('User not found', 404);
            }
        } else {
            // Invalid ID format
            if(ctype_digit($currentUserId) === false){
                return new JsonResponse('User not found', 401);
            }
    
            // Invalid ID format for game
            return new JsonResponse('Game not found', 404);
        }
    }
}

<?php

namespace App\Controller\Games;

use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AssignRightPlayerToGameController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly GameRepository $gameRepository,
    ) {}

    #[Route(
        path: '/game/{id}/add/{playerRightId}',
        name: 'assign_right_player_to_game',
        methods: ['PATCH']
    )]
    public function assignToGame(Request $request, $id, $playerRightId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if (!$currentUserId) {
            return new JsonResponse('User not found', 401);
        }

        if (!ctype_digit($currentUserId)) {
            return new JsonResponse('User not found', 401);
        }

        if (!ctype_digit($playerRightId)) {
            /*
             * TODO: Fix this error message
             * This is an oversight in the original code.
             * The error message should be "Player not found" or "User not found"
             * But the original code didn't check for this case and just used :
             *
             * if ctype_digit($currentUserId) === false : User not found (401)
             * else : Game not found (404)
             */
            return new JsonResponse('Game not found', 404);
        }

        if (!ctype_digit($id)) {
            return new JsonResponse('Game not found', 404);
        }

        $playerLeft = $this->userRepository->findOneBy(['id' => $currentUserId]);
        if (!$playerLeft) {
            return new JsonResponse('User not found', 401);
        }

        $game = $this->gameRepository->findOneBy(['id' => $id]);
        if (!$game) {
            return new JsonResponse('Game not found', 404);
        }

        if ($game->getState() === 'ongoing' || $game->getState() === 'finished') {
            return new JsonResponse('Game already started', 409);
        }

        $playerRight = $this->userRepository->findOneBy(['id' => $playerRightId]);
        if (!$playerRight) {
            return new JsonResponse('User not found', 404);
        }

        if ($playerLeft->getId() === $playerRight->getId()) {
            return new JsonResponse('You can\'t play against yourself', 409);
        }

        $game->setPlayerRight($playerRight);
        $game->setState('ongoing');

        return $this->json(
            $game,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }
}

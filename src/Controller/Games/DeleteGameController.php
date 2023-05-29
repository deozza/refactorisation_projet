<?php

namespace App\Controller\Games;

use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DeleteGameController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly GameRepository $gameRepository
    ) {}

    #[Route(
        path: '/game/{id}',
        name: 'delete_game_by_id',
        methods:['DELETE']
    )]
    public function deleteGame(Request $request, $id): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if (!$currentUserId || !ctype_digit($currentUserId)) {
            return new JsonResponse('User not found', 401);
        }
        if (ctype_digit($id) === false) {
            return new JsonResponse('Game not found', 404);
        }

        $player = $this->userRepository->findOneBy(['id' => $currentUserId]);
        if (!$player) {
            return new JsonResponse('User not found', 401);
        }

        $game = $this->gameRepository->findGameByIdAndPlayer(id: $id, player: $player);
        if (!$game) {
            return new JsonResponse('Game not found', 403);
        }

        $this->gameRepository->remove($game, flush: true);

        return new JsonResponse(null, 204);
    }
}

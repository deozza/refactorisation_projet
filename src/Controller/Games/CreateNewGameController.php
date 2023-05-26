<?php

namespace App\Controller\Games;

use App\Entity\Game;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class CreateNewGameController extends AbstractController
{
    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly UserRepository $userRepository
    ) {}

    #[Route(
        path: '/games',
        name: 'create_game',
        methods:['POST']
    )]
    public function launchGame(Request $request): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if (!$currentUserId) {
            return new JsonResponse('User not found', 401);
        }

        if (ctype_digit($currentUserId) === false) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $this->userRepository->findOneBy(['id' => $currentUserId]);

        if (!$currentUser) {
            return new JsonResponse('User not found', 401);
        }

        $newGame = new Game();
        $newGame->setState('pending');
        $newGame->setPlayerLeft($currentUser);
        $this->gameRepository->save($newGame, flush: true);

        return $this->json(
            data: $newGame,
            status: 201,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }
}

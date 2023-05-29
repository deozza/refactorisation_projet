<?php

namespace App\Controller\Games;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class GetGameController extends AbstractController
{
    public function __construct(
        private readonly GameRepository $gameRepository,
    ) {}

    #[Route(
        path: '/game/{id}',
        name: 'fetch_game',
        methods: ['GET']
    )]
    public function getGameInfo($id): JsonResponse
    {
        if (!ctype_digit($id)) {
            return new JsonResponse('Game not found', 404);
        }

        $game = $this->gameRepository->findOneBy(['id' => $id]);
        if (!$game) {
            return new JsonResponse('Game not found', 404);
        }

        return $this->json(
            $game,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }
}

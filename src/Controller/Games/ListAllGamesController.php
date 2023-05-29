<?php

namespace App\Controller\Games;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class ListAllGamesController extends AbstractController
{
    public function __construct(
        private readonly GameRepository $gameRepository
    ) {}

    #[Route(
        path:'/games',
        name: 'list_all_games',
        methods: ['GET']
    )]
    public function getGamesList(): JsonResponse
    {
        $data = $this->gameRepository->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }
}

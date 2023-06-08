<?php

namespace App\UseCase;

use App\Service\GameService;

class GameUseCase
{
    private GameService $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    /**
     * @return Game[]
     */
    public function getGameList(): array
    {
        return $this->gameService->getGameList();
    }
}

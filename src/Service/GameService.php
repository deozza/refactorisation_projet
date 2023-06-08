<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameRepository;

class GameService
{

    private GameRepository $gameRepository;

    public function __construct(GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }

    /**
     * @return Game[]
     */
    public function getGameList(): array
    {
        return $this->gameRepository->findAll();
    }


    /**
     * @param Game|null $game
     * 
     * @return void
     */
    public function save(?Game $game = null): void
    {
        if(empty($game) === false){
            $this->gameRepository->persist($game);
        }

        $this->gameRepository->flush();
    }

    /**
     * @param Game $game
     * 
     * @return void
     */
    public function delete(Game $game): void
    {
        $this->gameRepository->remove($game);
        $this->save();
    }
}

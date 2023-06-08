<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
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
     * @param User $playerLeft
     * 
     * @return Game
     */
    public function initGame(User $playerLeft): Game
    {
        $game = new Game();
        $game->setPlayerLeft($playerLeft);
        $game->setState(Game::STATE_PENDING);

        return $game;
    }

    /**
     * @param int $id
     * 
     * @return Game|null
     */
    public function getGameById(int $id): Game | null
    {
        return $this->gameRepository->find($id);
    }

    /**
     * @param Game $game
     * @param User $playerRight
     * 
     * @return Game
     */
    public function addPlayerRight(Game $game, User $playerRight): Game
    {
        $game->setPlayerRight($playerRight);
        $game->setState(GAME::STATE_ONGOING);

        return $game;
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

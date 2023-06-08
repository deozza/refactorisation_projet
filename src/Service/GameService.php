<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\PlayerChoiceOnGame;
use App\Entity\User;
use App\Form\PlayerChoiceOnGameType;
use App\Repository\GameRepository;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;

class GameService
{

    private GameRepository $gameRepository;
    private FormFactoryInterface $formFactory;
    
    public function __construct(GameRepository $gameRepository, FormFactoryInterface $formFactory)
    {
        $this->gameRepository = $gameRepository;
        $this->formFactory = $formFactory;
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
     * @param User $player
     * @param int $gameId
     * 
     * @return Game|null
     */
    public function getGameByEitherPlayer(User $player, int $gameId): Game | null
    {
        return $this->gameRepository->getGameByEitherPlayer($player, $gameId);
    }

    /**
     * @param User $player
     * @param Game $game
     * 
     * @return bool
     */
    public function checkIfUserIsAGamePlayer(User $player, Game $game): bool
    {
        return $player === $game->getPlayerLeft() || $player === $game->getPlayerRight();
    }

    /**
     * @param Game $game
     * 
     * @return bool
     */
    public function checkIfPlayersCanPlayOnGame(Game $game): bool
    {
        return Game::STATE_ONGOING === $game->getState();
    }

    /**
     * @param array $input
     * 
     * @return PlayerChoiceOnGame|FormErrorIterator
     */
    public function validatePlayerChoice(array $input): PlayerChoiceOnGame | FormErrorIterator
    {
        $playerChoiceOnGame = new PlayerChoiceOnGame();

        $playerChoiceOnGameForm = $this->formFactory->create(PlayerChoiceOnGameType::class, $playerChoiceOnGame);
        $playerChoiceOnGameForm->submit($input);

        if($playerChoiceOnGameForm->isValid() === false){
            return $playerChoiceOnGameForm->getErrors();
        }

        return $playerChoiceOnGameForm;
    }

    /**
     * @param Game $game
     * @param User $player
     * @param PlayerChoiceOnGame $playerChoice
     */
    public function addPlayerChoice(Game $game, User $player, PlayerChoiceOnGame $playerChoice): Game
    {
        if($game->getPlayerLeft() === $player){
            $game->setPlayLeft($playerChoice->getChoice());

            return $game;
        }

        $game->setPlayRight($playerChoice->getChoice());

        return $game;
    }

    public function computeGameResult(Game $game): Game
    {
        if(empty($game->getPlayLeft()) || empty($game->getPlayRight())){
            return $game;
        }

        $game->setState(Game::STATE_FINISHED);

        if($game->getPlayLeft() === $game->getPlayRight()){
            $game->setResult(Game::RESULT_DRAW);
            return $game;
        }

        $game->setResult(Game::RESULT_WIN_LEFT);

        if($game->getPlayLeft() === PlayerChoiceOnGame::CHOICE_ROCK && $game->getPlayRight() === playerchoiceongame::CHOICE_PAPER){
            $game->setResult(Game::RESULT_WIN_RIGHT);
        }

        if($game->getPlayLeft() === PlayerChoiceOnGame::CHOICE_PAPER && $game->getPlayRight() === playerchoiceongame::CHOICE_SCISSORS){
            $game->setResult(Game::RESULT_WIN_RIGHT);
        }

        if($game->getPlayLeft() === PlayerChoiceOnGame::CHOICE_SCISSORS && $game->getPlayRight() === playerchoiceongame::CHOICE_ROCK){
            $game->setResult(Game::RESULT_WIN_RIGHT);
        }

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

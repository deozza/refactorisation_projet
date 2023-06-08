<?php

namespace App\UseCase;

use App\Entity\Game;
use App\Service\GameService;
use App\Service\UserService;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class GameUseCase
{
    private GameService $gameService;
    private UserService $userService;

    public function __construct(GameService $gameService, UserService $userService)
    {
        $this->gameService = $gameService;
        $this->userService = $userService;
    }

    /**
     * @return Game[]
     */
    public function getGameList(): array
    {
        return $this->gameService->getGameList();
    }

    /**
     * @param int $currentUserId
     * 
     * @return Game
     * 
     * @throw UnauthorizedHttpException
     */
    public function createGame(int $currentUserId): Game
    {
        $playerLeft = $this->userService->getUserById($currentUserId);

        if(empty($playerLeft)){
            throw new UnauthorizedHttpException('User not found');
        }

        $game = $this->gameService->initGame($playerLeft);

        $this->gameService->save($game);

        return $game;
    }

    /**
     * @param int $id
     * 
     * @return Game
     * 
     * @throw NotFoundHttpException
     */
    public function getGameById(int $id): Game 
    {
        $game = $this->gameService->getGameById($id);

        if(empty($game)){
            throw new NotFoundHttpException('Game not found');
        }

        return $game;
    }

    /**
     * @param int $currentUserId
     * @param int $gameId
     * @param int $playerRight
     * 
     * @return Game
     * 
     * @throw UnauthorizedHttpException
     * @throw NotFoundHttpException
     * @throw ConflictHttpException
     */
    public function addPlayerRightToGame(int $currentUserId, int $gameId, int $playerRightId): Game
    {
        $playerLeft = $this->userService->getUserById($currentUserId);

        if(empty($playerLeft)){
            throw new UnauthorizedHttpException('User not found');
        }

        $game = $this->gameService->getGameById($gameId);

        if(empty($game)){
            throw new NotFoundHttpException('User not found');
        }

        if($game->getState() !== Game::STATE_PENDING){
            throw new ConflictHttpException('Game already started');
        }
        
        $playerRight = $this->userService->getUserById($playerRightId);

        if(empty($playerLeft)){
            throw new NotFoundHttpException('User not found');
        }

        if($currentUserId === $playerRightId){
            throw new ConflictHttpException("You can't play against yourself");   
        }

        $updatedGame = $this->gameService->addPlayerRight($game, $playerRight);

        $this->gameService->save();

        return $updatedGame;
    }

    public function addChoiceToGame(int $playerId, int $gameId, array $input): Game
    {
        $player = $this->userService->getUserById($playerId);
        if(empty($player)){
            throw new UnauthorizedHttpException('User not found');
        }

        $game = $this->gameService->getGameById($gameId);
        if(empty($game)){
            throw new NotFoundHttpException('Game not found');
        }

        $isAPlayer = $this->gameService->checkIfUserIsAGamePlayer($player, $game);
        if($isAPlayer === false){
            throw new AccessDeniedHttpException('You are not a player of this game');
        }

        $playersCanPlayOnGame = $this->gameService->checkIfPlayersCanPlayOnGame($game);
        if($playersCanPlayOnGame === false){
            throw new ConflictHttpException('Game not started');
        }

        $playerChoice = $this->gameService->validatePlayerChoice($input);
        if($playerChoice instanceof FormErrorIterator){
            throw new BadRequestException('Invalid choice');
        }

        $updatedGame = $this->gameService->addPlayerChoice($game, $player, $playerChoice);

        $updatedGame = $this->gameService->computeGameResult($game);

        $this->gameService->save();
        
        return $updatedGame;
    }

    /**
     * @param int $playerId
     * @param int $gameId
     */
    public function deleteGame(int $playerId, int $gameId): void
    {
        $player = $this->userService->getUserById($playerId);

        if(empty($player)){
            throw new UnauthorizedHttpException('User not found');
        }

        $game = $this->gameService->getGameByEitherPlayer($player, $gameId);

        if(empty($game)){
            throw new AccessDeniedHttpException('Game not found');
        }

        $this->gameService->delete($game);
    }
}

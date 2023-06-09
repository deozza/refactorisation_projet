<?php

namespace App\Controller;

use App\UseCase\GameUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class GameController extends AbstractController
{

    private GameUseCase $gameUseCase;

    public function __construct(GameUseCase $gameUseCase)
    {
        $this->gameUseCase = $gameUseCase;
    }

    #[Route('/games', name: 'get_game_list', methods:['GET'])]
    public function getGameList(): JsonResponse
    {
        $games = $this->gameUseCase->getGameList();
        return $this->json(
            $games,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/games', name: 'post_game', methods:['POST'])]
    public function postGame(Request $request): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if($this->checkUserHasToken($currentUserId) === false){
            return $this->json(
                'User not Found',
                Response::HTTP_UNAUTHORIZED,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }

        try{
            $createdGame = $this->gameUseCase->createGame($currentUserId);

            return $this->json(
                $createdGame,
                Response::HTTP_CREATED,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }catch(UnauthorizedHttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{gameId}', name: 'get_game_by_id', methods:['GET'], requirements:['gameId' => '\d+'])]
    public function getGameById(int $gameId): JsonResponse
    {
        try{
            $game = $this->gameUseCase->getGameById($gameId);

            return $this->json(
                $game,
                Response::HTTP_OK,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }catch(NotFoundHttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{gameId}/add/{playerRightId}', name: 'add_player_right_to_game', methods:['PATCH'], requirements:['gameId' => '\d+', 'playerRightId' => '\d+'])]
    public function addPlayerRightToGame(Request $request, int $gameId, int $playerRightId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if($this->checkUserHasToken($currentUserId) === false){
            return $this->json(
                'User not Found',
                Response::HTTP_UNAUTHORIZED,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }

        try{
            $updatedGame = $this->gameUseCase->addPlayerRightToGame($currentUserId, $gameId, $playerRightId);
            return $this->json(
                $updatedGame,
                Response::HTTP_OK,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }catch(HttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{gameId}', name: 'add_choice_to_game', methods:['PATCH'], requirements:['gameId' => '\d+'])]
    public function addChoiceToGame(Request $request, int $gameId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if($this->checkUserHasToken($currentUserId) === false){
            return $this->json(
                'User not Found',
                Response::HTTP_UNAUTHORIZED,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }

        $inputAsArray = json_decode($request->getContent(), true);
        if($inputAsArray === null){
            $inputAsArray = [];
        }

        try{
            $updatedGame = $this->gameUseCase->addChoiceToGame($currentUserId, $gameId, $inputAsArray);
            return $this->json(
                $updatedGame,
                Response::HTTP_OK,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }catch(HttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );   
        }
    }

    #[Route('/game/{gameId}', name: 'delete_game', methods:['DELETE'], requirements:['gameId' => '\d+'])]
    public function deleteGame(Request $request, int $gameId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if($this->checkUserHasToken($currentUserId) === false){
            return $this->json(
                'User not Found',
                Response::HTTP_UNAUTHORIZED,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }

        try{
            $this->gameUseCase->deleteGame($currentUserId, $gameId);
            return $this->json(
                null,
                Response::HTTP_NO_CONTENT,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        }catch(HttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    /**
     * @param $currentUserId
     * 
     * @return bool
     */
    private function checkUserHasToken($currentUserId): bool
    {
        if($currentUserId === null || ctype_digit($currentUserId) === false){
            return false;
        }

        return true;
    }
}

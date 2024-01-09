<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Validator\Constraints as Assert;
class GameController extends AbstractController
{
    private GameUse $gameUse;

    public function __construct(GameUse $gameUse) {
        $this->gameUse = $gameUse;
    }

    #[Route('/games', name: 'get_list_of_games', methods:['GET'])]
    public function getPartieList(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(Game::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/games', name: 'create_game', methods:['POST'])]
    public function createGame(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if ($this->checkUserIdIsNumber($currentUserId) === false) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if ($currentUser === null) {
            return new JsonResponse('User not found', 401);
        }
    
        $nouvelle_partie = (new Game())
            ->setState('pending')
            ->setPlayerLeft($currentUser);
        
        $entityManager->persist($nouvelle_partie);
        $entityManager->flush();
        
        return $this->json($nouvelle_partie, 201, ['Content-Type' => 'application/json;charset=UTF-8']);
    }

    #[Route('/game/{identifiant}', name: 'fetch_game', methods:['GET'])]
    public function getGameInfo(EntityManagerInterface $entityManager, $identifiant): JsonResponse
    {
        try{
            $game = $this->gameUse->getGameInfo($gameId);
            return new JsonResponse('Playser Found', 200);
        }catch(NotFoundHttpException $exception){
            return $this->json(
                $exception->getMessage(),
                $exception->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{id}/add/{playerRightId}', name: 'add_player_right', methods:['PATCH'])]
    public function addPlayerRight(Request $request, EntityManagerInterface $entityManager, $gameId, $playerRightId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if($this->checkUserIdIsNumber($currentUserId) === false){
            return new JsonResponse('User not found', 401);
        }

        try {
            $updatedGame = $this->gameUse->addPlayerRight($currentUserId, $gameId, $playerRightId);
            return new JsonResponse('Playser Found', 200);
        } catch(HttpException $exception) {
            return $this->json(
                $exception->$getMessage(),
                $exception->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{identifiant}', name: 'send_choice', methods:['PATCH'])]
    public function playGame(Request $request, EntityManagerInterface $entityManager, $identifiant): JsonResponse
    {
        {
            $currentUserId = $request->headers->get('X-User-Id');
            if($this->checkUserIdIsNumber($currentUserId) === false){
                return new JsonResponse('User not found', 401);
            }
    
            $dataAsArray = json_decode($request->getContent(), true);
            if($dataAsArray === null){
                $dataAsArray = [];
            }
    
            try{
                $updatedGame = $this->gameUse->playGame($currentUserId, $gameId, $dataAsArray);
                return new JsonResponse('Game found', 200);
            }catch(HttpException $exception){
                return $this->json(
                    $exception->getMessage(),
                    $exception->getStatusCode(),
                    ['Content-Type' => 'application/json;charset=UTF-8']
                );   
            }
        }
    #[Route('/game/{id}', name: 'annuler_game', methods:['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {
   
        $currentUserId = $request->headers->get('X-User-Id');

        if(ctype_digit($currentUserId) === true){
            $player = $entityManager->getRepository(User::class)->find($currentUserId);

            if($player !== null){

                if(ctype_digit($id) === false){
                    return new JsonResponse('Game not found', 404);
                }
        
                $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerLeft' => $player]);

                if(empty($game)){
                    $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerRight' => $player]);
                }

                if(empty($game)){
                    return new JsonResponse('Game not found', 403);
                }

                $entityManager->remove($game);
                $entityManager->flush();

                return new JsonResponse(null, 204);

            }else{
                return new JsonResponse('User not found', 401);
            }
        }else{
            return new JsonResponse('User not found', 401);
        }
    }

    private function checkUserIdIsNumber($currentUserId): bool {
        if ($currentUserId === null || ctype_digit($currentUserId) === false) {
            return false;
        } else {
            return true;
        }
    }
}

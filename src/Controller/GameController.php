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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
class GameController extends AbstractController
{
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

    #[Route('/game/{identifiant}', name: 'fetch_game', methods: ['GET'])]
    public function getGameInfo(EntityManagerInterface $entityManager, $identifiant): JsonResponse
    {
        try {
            $game = $entityManager->getRepository(Game::class)->find($identifiant);

            if ($game === null) {
                throw new NotFoundHttpException('Game not found');
            }

            return $this->json($game, 200, ['Content-Type' => 'application/json;charset=UTF-8']);
        } catch (NotFoundHttpException $exception) {
            return $this->json(
                $exception->getMessage(),
                $exception->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{gameId}/add/{playerRightId}', name: 'add_player_right', methods: ['PATCH'])]
    public function addPlayerRight(Request $request, EntityManagerInterface $entityManager, $gameId, $playerRightId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if ($this->checkUserIdIsNumber($currentUserId) === false) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if ($currentUser === null) {
            return new JsonResponse('User not found', 401);
        }

        try {
            $game = $entityManager->getRepository(Game::class)->find($gameId);

            if ($game === null) {
                throw new NotFoundHttpException('Game not found');
            }

            if($game->getState() === 'ongoing' || $game->getState() === 'finished'){
                return new JsonResponse('Game already started', 409);
            }

            $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);

            if ($playerRight === null) {
                return new JsonResponse('User not found', 404);
            }

            if ($playerRight === $currentUser) {
                return new JsonResponse('Play Yourself', 409);
            }
            

            return $this->json($game, 200, ['Content-Type' => 'application/json;charset=UTF-8']);
        } catch (HttpException $exception) {
            return $this->json(
                $exception->getMessage(),
                $exception->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{identifiant}', name: 'send_choice', methods: ['PATCH'])]
    public function playGame(Request $request, EntityManagerInterface $entityManager, $identifiant): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if ($this->checkUserIdIsNumber($currentUserId) === false) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);
        if ($currentUser === null) {
            return new JsonResponse('User not found', 401);
        }

        if ($this->checkUserIdIsNumber($identifiant) === false) {
            return new JsonResponse('Game not found', 404);
        }

        $game = $entityManager->getRepository(Game::class)->find($identifiant);

        if($game === null){
            return new JsonResponse('Game not found', 404);
        }


        $dataAsArray = json_decode($request->getContent(), true);
        if ($dataAsArray === null) {
            $dataAsArray = [];
        }

        try {
            $game = $entityManager->getRepository(Game::class)->find($identifiant);

            if ($game === null) {
                throw new NotFoundHttpException('Game not found');
            }

            $userIsPlayerLeft = false;
            $userIsPlayerRight = $userIsPlayerLeft;

            if($game->getPlayerLeft()->getId() === $currentUser->getId()){
                $userIsPlayerLeft = true;
            }elseif($game->getPlayerRight()->getId() === $currentUser->getId()){
                $userIsPlayerRight = true;
            }

            if(false === $userIsPlayerLeft && !$userIsPlayerRight){
                return new JsonResponse('You are not a player of this game', 403);
            }

            if($game->getState() === 'finished' || $game->getState() === 'pending'){
                return new JsonResponse('Game not started', 409);
            }

            $form = $this->createFormBuilder()
            ->add('choice', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->getForm();

            $choice = json_decode($request->getContent(), true);
            $form->submit($choice);
            $data = $form->getData();

            if($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors'){
                return new JsonResponse('Invalid choice', 400);
            }

            if ($userIsPlayerLeft) {
                $game->setPlayLeft($data['choice']);
                $entityManager->flush();
            
                if ($game->getPlayRight() !== null) {
                    $result = $this->calculateGameResult($game->getPlayLeft(), $game->getPlayRight());
            
                    $game->setResult($result);
                    $game->setState('finished');
                    $entityManager->flush();
                }
            
                return $this->json($game, headers: ['Content-Type' => 'application/json;charset=UTF-8']);
            }elseif ($userIsPlayerRight) {
                $game->setPlayRight($data['choice']);
                $entityManager->flush();
            
                if ($game->getPlayLeft() !== null) {
                    $result = $this->calculateGameResult($game->getPlayLeft(), $game->getPlayRight());
            
                    $game->setResult($result);
                    $game->setState('finished');
                    $entityManager->flush();
                }
            
                return $this->json($game, headers: ['Content-Type' => 'application/json;charset=UTF-8']);
            }
            return $this->json($game, 200, ['Content-Type' => 'application/json;charset=UTF-8']);
        } catch (HttpException $exception) {
            return $this->json(
                $exception->getMessage(),
                $exception->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{gameId}', name: 'delete_game', methods: ['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $gameId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if ($this->checkUserIdIsNumber($currentUserId) === false) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);
        if ($currentUser === null) {
            return new JsonResponse('User not found', 401);
        }

        try {
            $game = $entityManager->getRepository(Game::class)->find($gameId);

            if ($game === null) {
                throw new NotFoundHttpException('Game not found');
            }

            $player = $entityManager->getRepository(User::class)->find($currentUserId);
            $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $gameId, 'playerLeft' => $player]);

            if(empty($game)){
                $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $gameId, 'playerRight' => $player]);
            }
            if(empty($game)){
                return new JsonResponse('Game not found', 403);
            }

            $entityManager->remove($game);
            $entityManager->flush();

            return $this->json(
                null,
                Response::HTTP_NO_CONTENT,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        } catch (HttpException $exception) {
            return $this->json(
                $exception->getMessage(),
                $exception->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    private function calculateGameResult(string $playLeft, string $playRight): string
    {
        if ($playLeft === $playRight) {
            return 'draw';
        }

        $winningMoves = [
            'rock' => 'scissors',
            'paper' => 'rock',
            'scissors' => 'paper',
        ];

        if ($winningMoves[$playLeft] === $playRight) {
            return 'winLeft';
        }

        return 'winRight';
    }

    private function checkUserIdIsNumber($currentUserId): bool {
        if ($currentUserId === null || ctype_digit($currentUserId) === false) {
            return false;
        } else {
            return true;
        }
    }
}

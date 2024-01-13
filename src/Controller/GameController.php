<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Form\CreateChoiceType;


class GameController extends AbstractController
{
    #[Route('/games', name: 'get_list_of_games', methods: ['GET'])]
    public function getGamesList(EntityManagerInterface $entityManager): JsonResponse
    {
        $gamesList = $entityManager->getRepository(Game::class)->findAll();
        return $this->json(
            $gamesList,
            Response::HTTP_OK,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/games', name: 'create_game', methods: ['POST'])]
    public function launchGame(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if (!$currentUserId || !ctype_digit($currentUserId)) { //OK
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);
        if (!$currentUser) { //OK
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $newGame = new Game();
        $newGame->setState('pending');
        $newGame->setPlayerLeft($currentUser);

        $entityManager->persist($newGame);

        $entityManager->flush();

        return $this->json(
            $newGame,
            Response::HTTP_CREATED,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{gameId}', name: 'fetch_game', methods: ['GET'])]
    public function getGameInfo(EntityManagerInterface $entityManager, $gameId): JsonResponse
    {
        
        if(!ctype_digit($gameId)){ //OK
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }
        $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $gameId]);
        if (!$game) { //OK
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json(
            $game,
            Response::HTTP_OK,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{id}/add/{playerRightId}', name: 'add_user_right', methods: ['PATCH'])]
    public function inviteToGame(Request $request, EntityManagerInterface $entityManager, $id, $playerRightId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if(empty($currentUserId)){ //OK
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED);
        }

        $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);
        if(!$playerLeft){ //OK
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);
        if (!$playerRight) { //OK
            return new JsonResponse(
                'User not found',
                Response::HTTP_NOT_FOUND
            );
        }
        $game = $entityManager->getRepository(Game::class)->find($id);
        if (!$game) { //OK
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }

        if(!ctype_digit($id) && !ctype_digit($playerRightId) && !ctype_digit($currentUserId)){
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND,
            );
        }

        if ($game->getState() === 'ongoing' || $game->getState() === 'finished') { //OK
            return new JsonResponse(
                'Game already started',
                Response::HTTP_CONFLICT
            );
        }
        if ($playerLeft->getId() === $playerRight->getId()) { //OK
            return new JsonResponse(
                'You can\'t play against yourself',
                Response::HTTP_CONFLICT
            );
        }

        $game->setPlayerRight($playerRight);
        $game->setState('ongoing');

        $entityManager->flush();

        return $this->json(
            $game,
            Response::HTTP_OK,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{gameId}', name: 'send_choice', methods: ['PATCH'])]
    public function play(Request $request, EntityManagerInterface $entityManager, $gameId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if(!ctype_digit($currentUserId)){ //OK
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);
        if (!$currentUser){ //OK
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if(!ctype_digit($gameId)){ //OK
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }
        $game = $entityManager->getRepository(Game::class)->find($gameId);
        if (!$game) { //OK
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }

        $userIsPlayerLeft = false;
        $userIsPlayerRight = false;

        if ($game->getPlayerLeft()->getId() === $currentUser->getId()) {
            $userIsPlayerLeft = true;
        } elseif ($game->getPlayerRight()->getId() === $currentUser->getId()) {
            $userIsPlayerRight = true;
        }

        if (!$userIsPlayerLeft && !$userIsPlayerRight) { //OK
            return new JsonResponse(
                'You are not a player of this game',
                Response::HTTP_FORBIDDEN,
            );
        }
        if ($game->getState() === 'finished' || $game->getState() === 'pending') { //OK
            return new JsonResponse(
                'Game not started', 
                Response::HTTP_CONFLICT,
            );
        }
        $form = $this->createForm(CreateChoiceType::class);
        $choice = json_decode($request->getContent(), true);
        $form->submit($choice);

        if(!$form->isValid()){ //OK
            return new JsonResponse(
                'Invalid choice',
                Response::HTTP_BAD_REQUEST,
            );
        }

        $data = $form->getData();

        // we play with the Shifumi's rules
        if ($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors') { //OK
            return new JsonResponse(
                'Invalid choice',
                Response::HTTP_BAD_REQUEST,
            );
        }
        
        if ($userIsPlayerLeft) {
            $game->setPlayLeft($data['choice']);
            $entityManager->flush();
            
            return $this->json(
                $game,
                Response::HTTP_OK,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
        } elseif ($userIsPlayerRight) {
            $game->setPlayRight($data['choice']);
            $entityManager->flush();

            switch ($data['choice']) {
                case 'rock':
                    if($game->getPlayLeft() !== 'paper' && $game->getPlayLeft() !== 'scissors'){
                        $game->setResult('draw');
                    }
                    if ($game->getPlayLeft() === 'paper') {
                        $game->setResult('winLeft');
                    } elseif ($game->getPlayLeft() === 'scissors') {
                        $game->setResult('winRight');
                    }
                    break;
                case 'paper':
                    if($game->getPlayLeft() !== 'scissors' && $game->getPlayLeft() !== 'rock'){
                        $game->setResult('draw');
                    }
                    if ($game->getPlayLeft() === 'scissors') {
                        $game->setResult('winLeft');
                    } elseif ($game->getPlayLeft() === 'rock') {
                        $game->setResult('winRight');
                    }
                    break;
                case 'scissors':
                    if($game->getPlayLeft() !== 'rock' && $game->getPlayLeft() !== 'paper'){
                        $game->setResult('draw');
                    }
                    if ($game->getPlayLeft() === 'rock') {
                        $game->setResult('winLeft');
                    } elseif ($game->getPlayLeft() === 'paper') {
                        $game->setResult('winRight');
                    }
                    break;
            }
            $game->setState('finished');
            $entityManager->flush();

            return $this->json(
                $game,
                Response::HTTP_OK,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/game/{gameId}', name: 'delete_game', methods: ['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $gameId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if (!ctype_digit($currentUserId)) { //OK
            return new JsonResponse(
                'User not found', 
                Response::HTTP_UNAUTHORIZED
            );
        }

        if(!ctype_digit($gameId)){
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND);
        }
        
        $player = $entityManager->getRepository(User::class)->find($currentUserId);
        if(null === $player){ //OK
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $gameId, 'playerLeft' => $player]);
        if (empty($game)) {
            $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $gameId, 'playerRight' => $player]);
        }
        if (empty($game)) { //OK
            return new JsonResponse(
                'Game not found',
                Response::HTTP_FORBIDDEN
            );
        }

        $entityManager->remove($game);
        $entityManager->flush();

        return new JsonResponse( //OK
            null,
            Response::HTTP_NO_CONTENT,
        );        
    }
}
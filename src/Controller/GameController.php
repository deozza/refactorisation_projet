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
use App\Form\CreateChoiceType;

use Symfony\Component\Validator\Constraints as Assert;

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

        if (null === $currentUserId) {
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);
        if (null === $currentUser) {
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
        $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $gameId]);

        if ($game === null) {
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
        $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);
        $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);
        $game = $entityManager->getRepository(Game::class)->find($id);

        if(empty($currentUserId)){
            return new JsonResponse(
                'User not found',
                401);
        }
        if(null === $playerLeft){
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }
        if (null === $playerRight) {
            return new JsonResponse(
                'User not found',
                Response::HTTP_NOT_FOUND
            );
        }
        if(empty($currentUserId)){
            return new JsonResponse(
                'User not found',
                401);
        }
        if (null === $game) {
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }
        if ($game->getState() === 'ongoing' || $game->getState() === 'finished') {
            return new JsonResponse(
                'Game already started',
                Response::HTTP_CONFLICT
            );
        }
        if ($playerLeft->getId() === $playerRight->getId()) {
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
        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);
        $game = $entityManager->getRepository(Game::class)->find($gameId);
        $userIsPlayerLeft = false;
        $userIsPlayerRight = false;

        if(ctype_digit($currentUserId) === false){
            return new JsonResponse(
                'User not found',
                401);
        }

        if (null === $currentUser){
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }
        if (null === $game) {
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }
        if ($game->getPlayerLeft()->getId() === $currentUser->getId()) {
            $userIsPlayerLeft = true;
        } elseif ($game->getPlayerRight()->getId() === $currentUser->getId()) {
            $userIsPlayerRight = true;
        }
        if (!$userIsPlayerLeft && !$userIsPlayerRight) {
            return new JsonResponse(
                'You are not a player of this game',
                Response::HTTP_FORBIDDEN,
            );
        }
        if ($game->getState() === 'finished' || $game->getState() === 'pending') {
            return new JsonResponse(
                'Game not started', 
                Response::HTTP_CONFLICT,
            );
        }
        $form = $this->createForm(CreateChoiceType::class);
        $choice = json_decode($request->getContent(), true);
        $form->submit($choice);

        if(!$form->isValid()){
            return new JsonResponse(
                'The form is not valid',
                Response::HTTP_BAD_REQUEST,
            );
        }

        $data = $form->getData();

        // we play with the Shifumi's rules
        if ($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors') {
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

    #[Route('/game/{id}', name: 'delete_game', methods: ['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        $player = $entityManager->getRepository(User::class)->find($currentUserId);
        $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerLeft' => $player]);

        if(null === $player){
            return new JsonResponse(
                'User not found',
                Response::HTTP_UNAUTHORIZED,
            );
        }
        if (false === ctype_digit($id)) {
            return new JsonResponse(
                'User not found', 
                Response::HTTP_NOT_FOUND
            );
        }
        if (empty($game)) {
            $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerRight' => $player]);
        }
        if (empty($game)) {
            return new JsonResponse(
                'Game not found',
                Response::HTTP_FORBIDDEN
            );
        }

        $entityManager->remove($game);
        $entityManager->flush();

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
        );        
    }
}
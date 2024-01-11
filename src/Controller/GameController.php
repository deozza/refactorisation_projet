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
                'You to be a user !',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if (null === $currentUser) {
            return new JsonResponse(
                'You to be a user !',
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

        if (empty($currentUserId)) {
            return new JsonResponse(
                'You need to be a user !',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if (false === ctype_digit($currentUserId)) {
            return new JsonResponse(
                'Your user id doesn\'t respect the good format',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);

        if (null === $playerLeft) {
            return new JsonResponse(
                'You need to have a player left',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $game = $entityManager->getRepository(Game::class)->find($id);

        if (null === $game) {
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($game->getState() === 'ongoing' || $game->getState() === 'finished') {
            return new JsonResponse(
                'Conflict, it\'s impossible to start and finish a game in a same time !',
                Response::HTTP_CONFLICT
            );
        }

        $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);

        if (null === $playerRight) {
            return new JsonResponse(
                'Player right not found',
                Response::HTTP_NOT_FOUND
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

        if (null === $currentUser) {
            return new JsonResponse(
                'You need to be a player',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if (null === $game) {
            return new JsonResponse(
                'Game not found',
                Response::HTTP_NOT_FOUND
            );
        }

        $userIsPlayerLeft = false;
        $userIsPlayerRight = $userIsPlayerLeft;

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

        // we must check the game is ongoing and the user is a player of this game
        if ($game->getState() === 'finished' || $game->getState() === 'pending') {
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
                'You need to respect the rules !',
                Response::HTTP_BAD_REQUEST,
            );
        }

        if ($userIsPlayerLeft) {
            $game->setPlayLeft($data['choice']);
            $entityManager->flush();

            if (null !== $game->getPlayRight()) {

                switch ($data['choice']) {
                    case 'rock':
                        if($game->getPlayRight() !== 'paper' && $game->getPlayRight() !== 'scissors'){
                            $game->setResult('draw');
                        }
                        if ($game->getPlayRight() === 'paper') {
                            $game->setResult('winRight');
                        } elseif ($game->getPlayRight() === 'scissors') {
                            $game->setResult('winLeft');
                        }
                        break;
                    case 'paper':
                        if($game->getPlayRight() !== 'scissors' && $game->getPlayRight() !== 'rock'){
                            $game->setResult('draw');
                        }
                        if ($game->getPlayRight() === 'scissors') {
                            $game->setResult('winRight');
                        } elseif ($game->getPlayRight() === 'rock') {
                            $game->setResult('winLeft');
                        }
                        break;
                    case 'scissors':
                        if($game->getPlayRight() !== 'rock' && $game->getPlayRight() !== 'paper'){
                            $game->setResult('draw');
                        }
                        if ($game->getPlayRight() === 'rock') {
                            $game->setResult('winRight');
                        } elseif ($game->getPlayRight() === 'paper') {
                            $game->setResult('winLeft');
                        }
                        break;
                }

                $game->setState('finished');
                $entityManager->flush();
            }

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

    #[Route('/game/{id}', name: 'annuler_game', methods: ['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {

        $currentUserId = $request->headers->get('X-User-Id');

        $player = $entityManager->getRepository(User::class)->find($currentUserId);

        if(null === $player){
            return new JsonResponse(
                'It\'s unauthorized my friend',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if (false === ctype_digit($id)) {
            return new JsonResponse(
                'Game not found', 
                Response::HTTP_NOT_FOUND
            );
        }

        $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerLeft' => $player]);

        if (empty($game)) {
            $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerRight' => $player]);
        }

        if (empty($game)) {
            return new JsonResponse(
                'It\'s forbidden my friend',
                Response::HTTP_FORBIDDEN
            );
        }

        $entityManager->remove($game);
        $entityManager->flush();

        return new JsonResponse(
            "No content",
            Response::HTTP_NO_CONTENT,
        );        
    }
}
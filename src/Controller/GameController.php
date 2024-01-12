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
use App\Repository\GameRepository;

use Symfony\Component\Validator\Constraints as Assert;

class GameController extends AbstractController
{
    #[Route('/games', name: 'get_games_list', methods: ['GET'])]
    public function getGamesList(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(Game::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/games', name: 'create_game', methods: ['POST'])]
    public function createGame(Request $request, EntityManagerInterface $entityManager, GameRepository $gameRepository): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if ($currentUserId == null) {
            return new JsonResponse('User not found', 401);
        }

        if (ctype_digit($currentUserId) === false) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if ($currentUser === null) {
            return new JsonResponse('User not found', 401);
        }

        $newGame = new Game();
        $newGame->setState('pending');
        $newGame->setPlayerLeft($currentUser);

        $gameRepository->save($newGame, true);

        return $this->json(
            $newGame,
            201,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{gameId}', name: 'find_game_info_by_id', methods: ['GET'])]
    public function findGameInfoById(EntityManagerInterface $entityManager, $gameId): JsonResponse
    {
        if (!ctype_digit($gameId)) {
            return new JsonResponse('Game not found', 404);
        }

        $party = $entityManager->getRepository(Game::class)->findOneBy(['id' => $gameId]);

        if ($party == null) {
            return new JsonResponse('Game not found', 404);
        }

        return $this->json(
            $party,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{id}/add/{playerRightId}', name: 'invite_user_to_a_game', methods: ['PATCH'])]
    public function inviteToGame(Request $request, EntityManagerInterface $entityManager, $id, $playerRightId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if (empty($currentUserId)) {
            return new JsonResponse('User not found', 401);
        }

        if (!ctype_digit($id) && !ctype_digit($playerRightId) && !ctype_digit($currentUserId)) {
            if (ctype_digit($currentUserId) === false) {
                return new JsonResponse('User not found', 401);
            }
            return new JsonResponse('Game not found', 404);
        }

        $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);

        if ($playerLeft === null) {
            return new JsonResponse('User not found', 401);
        }

        $game = $entityManager->getRepository(Game::class)->find($id);

        if ($game === null) {
            return new JsonResponse('Game not found', 404);
        }

        if ($game->getState() === 'ongoing' || $game->getState() === 'finished') {
            return new JsonResponse('Game already started', 409);
        }

        $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);

        if ($playerRight == null) {
            return new JsonResponse('User not found', 404);
        }

        if ($playerLeft->getId() === $playerRight->getId()) {
            return new JsonResponse('You can\'t play against yourself', 409);
        }

        $game->setPlayerRight($playerRight);
        $game->setState('ongoing');

        $entityManager->flush();

        return $this->json(
            $game,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{gameId}', name: 'send_move_choice', methods: ['PATCH'])]
    public function playAMove(Request $request, EntityManagerInterface $entityManager, $gameId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if (ctype_digit($currentUserId) === false) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if ($currentUser === null) {
            return new JsonResponse('User not found', 401);
        }

        if (ctype_digit($gameId) === false) {
            return new JsonResponse('Game not found', 404);
        }

        $game = $entityManager->getRepository(Game::class)->find($gameId);

        if ($game === null) {
            return new JsonResponse('Game not found', 404);
        }

        $userIsPlayerLeft = false;
        $userIsPlayerRight = $userIsPlayerLeft;

        if ($game->getPlayerLeft()->getId() === $currentUser->getId()) {
            $userIsPlayerLeft = true;
        } elseif ($game->getPlayerRight()->getId() === $currentUser->getId()) {
            $userIsPlayerRight = true;
        }

        if (false === $userIsPlayerLeft && !$userIsPlayerRight) {
            return new JsonResponse('You are not a player of this game', 403);
        }

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

        if (!$form->isValid()) {
            return new JsonResponse('Invalid choice', 400);
        }

        $data = $form->getData();

        if ($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors') {
            return new JsonResponse('Invalid choice', 400);
        }

        if ($userIsPlayerLeft) {
            $game->setPlayLeft($data['choice']);
            $entityManager->flush();
        } else if ($userIsPlayerRight) {
            $game->setPlayRight($data['choice']);
            $entityManager->flush();
        }

        if ($userIsPlayerLeft && $game->getPlayRight() == null || $userIsPlayerRight && $game->getPlayLeft() == null) {
            return $this->json(
                $game,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }

        if ($userIsPlayerLeft) {
            calculateResultForLeftPlayer($game, $data);
        } elseif ($userIsPlayerRight) {
            calculateResultForRightPlayer($game, $data);
        }

        $game->setState('finished');
        $entityManager->flush();

        return $this->json(
            $game,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );

        return new JsonResponse('coucou');
    }

    #[Route('/game/{id}', name: 'cancel_game', methods: ['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $id, GameRepository $gameRepository): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if (!ctype_digit($currentUserId)) {
            return new JsonResponse('User not found', 401);
        }
        $player = $entityManager->getRepository(User::class)->find($currentUserId);

        if ($player == null) {
            return new JsonResponse('User not found', 401);
        }

        if (!ctype_digit($id)) {
            return new JsonResponse('Game not found', 404);
        }

        $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerLeft' => $player]);

        if (empty($game)) {
            $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerRight' => $player]);
        }

        if (empty($game)) {
            return new JsonResponse('Game not found', 403);
        }

        $gameRepository->remove($game, true);

        return new JsonResponse(null, 204);
    }
}

function calculateResultForLeftPlayer($game, $data)
{
    switch ($data['choice']) {
        case 'rock':
            if ($game->getPlayRight() === 'paper') {
                $game->setResult('winRight');
            } elseif ($game->getPlayRight() === 'scissors') {
                $game->setResult('winLeft');
            } else {
                $game->setResult('draw');
            }
            break;
        case 'paper':
            if ($game->getPlayRight() === 'scissors') {
                $game->setResult('winRight');
            } elseif ($game->getPlayRight() === 'rock') {
                $game->setResult('winLeft');
            } else {
                $game->setResult('draw');
            }
            break;
        case 'scissors':
            if ($game->getPlayRight() === 'rock') {
                $game->setResult('winRight');
            } elseif ($game->getPlayRight() === 'paper') {
                $game->setResult('winLeft');
            } else {
                $game->setResult('draw');
            }
            break;
    }
}
function calculateResultForRightPlayer($game, $data)
{
    switch ($data['choice']) {
        case 'rock':
            if ($game->getPlayLeft() === 'paper') {
                $game->setResult('winLeft');
            } elseif ($game->getPlayLeft() === 'scissors') {
                $game->setResult('winRight');
            } else {
                $game->setResult('draw');
            }
            break;
        case 'paper':
            if ($game->getPlayLeft() === 'scissors') {
                $game->setResult('winLeft');
            } elseif ($game->getPlayLeft() === 'rock') {
                $game->setResult('winRight');
            } else {
                $game->setResult('draw');
            }
            break;
        case 'scissors':
            if ($game->getPlayLeft() === 'rock') {
                $game->setResult('winLeft');
            } elseif ($game->getPlayLeft() === 'paper') {
                $game->setResult('winRight');
            } else {
                $game->setResult('draw');
            }
            break;
    }
}

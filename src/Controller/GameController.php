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
    # Liste les parties
    #[Route('/games', name: 'get_list_of_games', methods: ['GET'])]
    public function getPartieList(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(Game::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    # Créer une partie
    #[Route('/games', name: 'create_game', methods: ['POST'])]
    public function launchGame(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        # Cherche un id
        $currentUserId = $request->headers->get('X-User-Id');

        # Si il y a un ID et que c'est un nombre/chiffre
        if ($currentUserId !== null && ctype_digit($currentUserId)) {

            # Cherche un utilisateur
            $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

            # Retourne une erreur si l'utilisateur n'existe pas ou que l'id est null
            // Si l'utilisateur n'existe pas -> stop creation de partie
            if ($currentUser === null) {
                return new JsonResponse('User not found', 401);
            }

            # Créer une nouvelle partie
            $newGame = new Game();
            $newGame->setState('pending');
            $newGame->setPlayerLeft($currentUser);

            $entityManager->persist($newGame);

            $entityManager->flush();

            # Retourne une nouvelle partie au format json
            return $this->json(
                $newGame,
                201,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
            # Si il n'y a rien dans la requête header
        } else {
            return new JsonResponse('User ID is nul', 401);
        }
    }

    # Récupérer une partie
    #[Route('/game/{id}', name: 'fetch_game', methods: ['GET'])]
    public function getGameInfo(EntityManagerInterface $entityManager, $id): JsonResponse
    {
        # Si l'id est un nombre/chiffre
        if (ctype_digit($id)) {
            # Retrouve une partie en BDD
            $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id]);

            # Si une partie exite
            if ($game) {
                # Retourne la partie
                return $this->json(
                    $game,
                    headers: ['Content-Type' => 'application/json;charset=UTF-8']
                );
                # Si une partie n'existe pas
            } else {
                return new JsonResponse('Game not found', 404);
            }
            # Si l'id n'est pas un nombre/chiffre
        } else {
            return new JsonResponse('Game not found', 404);
        }
    }

    # Ajoute un joueur à une partie
    #[Route('/game/{id}/add/{playerRightId}', name: 'add_user_right', methods: ['PATCH'])]
    public function inviteToGame(Request $request, EntityManagerInterface $entityManager, $id, $playerRightId): JsonResponse
    {
        # Récupère un ID
        $currentUserId = $request->headers->get('X-User-Id');

        # Si il n'y a pas de ID
        if (empty($currentUserId)) {
            return new JsonResponse('User not found', 401);
        }

        # Si les id ne sont pas des nombres
        if (!ctype_digit($id) && !ctype_digit($playerRightId) && !ctype_digit($currentUserId)) {
            return new JsonResponse('Game not found', 404);
            if (!ctype_digit($currentUserId)) {
                return new JsonResponse('User not found', 401);
            }
        }

        # Récupère un utilisateur
        $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);

        # Si il n'y a pas de d'utilisateur
        if ($playerLeft === null) {
            return new JsonResponse('User not found', 401);
        }

        # Récupère une partie
        $game = $entityManager->getRepository(Game::class)->find($id);

        # Si il n'y a pas de partie
        if ($game === null) {
            return new JsonResponse('Game not found', 404);
        }

        # Si la partie est en mode ongoin ou finished
        if ($game->getState() === 'ongoing' || $game->getState() === 'finished') {
            return new JsonResponse('Game already started', 409);
        }

        # #Récupère un utilisateur
        $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);

        if ($playerRight === null) {
            return new JsonResponse('User not found', 404);
        }

        # Si l'utilisateur 1 est égal à l'utilisateur 2
        if ($playerLeft->getId() === $playerRight->getId()) {
            return new JsonResponse('You can\'t play against yourself', 409);
        }

        # Change l'état d'une partie ajoute le deuxième joueur
        $game->setPlayerRight($playerRight);
        $game->setState('ongoing');

        $entityManager->flush();

        return $this->json(
            $game,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{id}', name: 'send_choice', methods: ['PATCH'])]
    public function play(Request $request, EntityManagerInterface $entityManager, $id): JsonResponse
    {
        # Récupère l'id
        $currentUserId = $request->headers->get('X-User-Id');

        # Si l'id de l'utilisateur actuel n'est un nombre/chiffre
        if (ctype_digit($currentUserId) === false) {
            return new JsonResponse('User not found', 401);
        }

        # Recherche un utilisateur
        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        # Si l'utilisateur n'existe pas
        if ($currentUser === null) {
            return new JsonResponse('User not found', 401);
        }

        # Si l'id de la partie n'est pas un nombre/chiffre
        if (ctype_digit($id) === false) {
            return new JsonResponse('Game not found', 404);
        }

        # Recherche une partie
        $game = $entityManager->getRepository(Game::class)->find($id);

        # Si la partie n'existe pas
        if ($game === null) {
            return new JsonResponse('Game not found', 404);
        }

        # On créer deux variable qu'on met à false
        $userIsPlayerLeft = false;
        $userIsPlayerRight = $userIsPlayerLeft;

        # Si le joueur actuel correspond à un joueur enregistré pour la partie choisie
        if ($game->getPlayerLeft()->getId() === $currentUser->getId()) {
            $userIsPlayerLeft = true;
        } elseif ($game->getPlayerRight()->getId() === $currentUser->getId()) {
            $userIsPlayerRight = true;
        }

        # Si les jouerus ne correspondent pas à la partie en cours
        if (false === $userIsPlayerLeft && !$userIsPlayerRight) {
            return new JsonResponse('You are not a player of this game', 403);
        }

        // we must check the game is ongoing and the user is a player of this game
        if ($game->getState() === 'finished' || $game->getState() === 'pending') {
            return new JsonResponse('Game not started', 409);
        }

        # On verifie que le champs du formulaire choice n'est pas vide
        $form = $this->createFormBuilder()
            ->add('choice', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->getForm();

        $choice = json_decode($request->getContent(), true);

        $form->submit($choice);

        if (!($form->isValid())) {
            return new JsonResponse('Invalid choice', 400);
        }

        $data = $form->getData();

        // on joue avec les règles de base de pierre feuille ciseaux
        if ($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors') {
            return new JsonResponse('Invalid choice', 400);
        }

        if ($userIsPlayerLeft) {
            $game->setPlayLeft($data['choice']);
            $entityManager->flush();

            if ($game->getPlayRight() !== null) {

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

                $game->setState('finished');
                $entityManager->flush();

                return $this->json(
                    $game,
                    headers: ['Content-Type' => 'application/json;charset=UTF-8']
                );
            }

            return $this->json(
                $game,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
        } elseif ($userIsPlayerRight) {
            $game->setPlayRight($data['choice']);

            $entityManager->flush();

            if ($game->getPlayLeft() !== null) {

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

                $game->setState('finished');
                $entityManager->flush();

                return $this->json(
                    $game,
                    headers: ['Content-Type' => 'application/json;charset=UTF-8']
                );
            }
            return $this->json(
                $game,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }

        return new JsonResponse('coucou');
    }

    #[Route('/game/{id}', name: 'cancel_game', methods: ['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {

        $currentUserId = $request->headers->get('X-User-Id');

        if (ctype_digit($currentUserId) === true) {
            $player = $entityManager->getRepository(User::class)->find($currentUserId);

            if ($player !== null) {

                if (ctype_digit($id) === false) {
                    return new JsonResponse('Game not found', 404);
                }

                $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerLeft' => $player]);

                if (empty($game)) {
                    $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerRight' => $player]);
                }

                if (empty($game)) {
                    return new JsonResponse('Game not found', 403);
                }

                $entityManager->remove($game);
                $entityManager->flush();

                return new JsonResponse(null, 204);
            } else {
                return new JsonResponse('User not found', 401);
            }
        } else {
            return new JsonResponse('User not found', 401);
        }
    }
}

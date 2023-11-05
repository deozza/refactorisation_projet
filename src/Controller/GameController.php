<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Form\PlayerChoiceType;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class GameController extends AbstractController
{
    private $gameRepository;
    private $userRepository;
    private $entityManager;

    public function __construct(GameRepository $gameRepository, EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->gameRepository = $gameRepository;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/games', name: 'get_list_of_games', methods:['GET'])]
    public function getPartieList(): JsonResponse
    {
        $data = $this->gameRepository->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/games', name: 'create_game', methods:['POST'])]
    public function launchGame(Request $request): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if($currentUserId === null || !ctype_digit($currentUserId)){
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $this->userRepository->find($currentUserId);

        if($currentUser === null){
            return new JsonResponse('User not found', 401);
        }

        $newGame = $this->createNewGame($currentUser);

        return $this->json(
            $newGame,
            201,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    private function createNewGame(User $currentUser): Game
    {
        $newGame = new Game();
        $newGame->setState('pending');
        $newGame->setPlayerLeft($currentUser);

        $this->entityManager->persist($newGame);
        $this->entityManager->flush();

        return $newGame;
    }

    #[Route('/game/{identifiant}', name: 'fetch_game', methods:['GET'])]
    public function getGameInfo($identifiant): JsonResponse
    {
        if(!ctype_digit($identifiant)){
            return new JsonResponse('Game not found', 404);
        }

        $party = $this->entityManager->getRepository(Game::class)->findOneBy(['id' => $identifiant]);

        if($party === null){
            return new JsonResponse('Game not found', 404);
        }

        return $this->json(
            $party,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );     
    }

    #[Route('/game/{id}/add/{playerRightId}', name: 'add_user_right', methods:['PATCH'])]
    public function inviteToGame(
        Request $request, 
        EntityManagerInterface $entityManager, 
        $id, 
        $playerRightId
    ): JsonResponse {
        $currentUserId = $request->headers->get('X-User-Id');
        $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);
        $game = $entityManager->getRepository(Game::class)->find($id);
        $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);

    if (empty($currentUserId) || !ctype_digit($currentUserId) || $playerLeft === null) {
        return new JsonResponse('User not found', 401);
    }

    if (!ctype_digit($id) || !ctype_digit($playerRightId) || $game === null) {
        return new JsonResponse('Game not found', 404);
    }

    if ($game->getState() === 'ongoing' || $game->getState() === 'finished') {
        return new JsonResponse('Game already started', 409);
    }

    if ($playerRight === null) {
        return new JsonResponse('User not found', 404);
    }

    if ($playerLeft->getId() === $playerRight->getId()) {
        return new JsonResponse('You can\'t play against yourself', 409);
    }

    $game->setPlayerRight($playerRight);
    $game->setState('ongoing');
    $this->entityManager->flush();

    return $this->json(
        $game,
        headers: ['Content-Type' => 'application/json;charset=UTF-8']
    );
}

    
    #[Route('/game/{identifiant}', name: 'send_choice', methods:['PATCH'])]
    public function play(Request $request, EntityManagerInterface $entityManager, $identifiant): JsonResponse
    {
		//check if the user right or left is associated with a game
        $UserId = $request->headers->get('X-User-Id');
		$User = $entityManager->getRepository(User::class)->find($UserId);
		$game = $entityManager->getRepository(Game::class)->find($identifiant);

        if(ctype_digit($UserId) === false || $User === null){
            return new JsonResponse('User not found', 401);
        }
    
        if(ctype_digit($identifiant) === false || $game === null){
            return new JsonResponse('Game not found', 404);
        }

        $userIsPlayerLeft = false;
        $userIsPlayerRight = false;
        
        if($game->getPlayerLeft()->getId() === $User->getId()){
            $userIsPlayerLeft = true;
        } elseif($game->getPlayerRight()->getId() === $User->getId()){
            $userIsPlayerRight = true;
        }
        
        if(!$userIsPlayerLeft && !$userIsPlayerRight){
            return new JsonResponse('You are not a player of this game', 403);
        }

        // check if the game is ongoing and the user is a player of this game
        if($game->getState() === 'finished' || $game->getState() === 'pending'){
            return new JsonResponse('Game not started', 409);
        }

		$form = $this->createForm(PlayerChoiceType::class);
		$choice = json_decode($request->getContent(), true);
		$form->submit($choice);

        if($form->isValid()){
            $data = $form->getData();

            if($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors'){
                return new JsonResponse('Invalid choice', 400);
            }

			// Play with the rules of rock, paper, scissors
			if ($userIsPlayerLeft){
				$game->setPlayLeft($data['choice']);
				$entityManager->flush();
				if ($game->getPlayRight() !== null){
					$result = $this->defineWinner($data['choice'], $game->getPlayRight());
					$game->setResult($result);
				}
				$game->setState('finished');
				$entityManager->flush();
			} else if($userIsPlayerRight){
				$game->setPlayRight($data['choice']);
				$entityManager->flush();
				if($game->getPlayLeft() !== null){
					$result = $this->defineWinner($game->getPlayLeft(), $data['choice']);
					$game->setResult($result);
				}
				$game->setState('finished');
				$entityManager->flush();
			}
			return $this->json(
				$game,
				headers: ['Content-Type' => 'application/json;charset=UTF-8']
			);
		}
        return new JsonResponse('Invalid choice', 400);
    }

	private function defineWinner($leftChoice, $rightChoice){
		if ($leftChoice === $rightChoice){
			return 'draw';
		}

		if (($leftChoice === 'rock' && $rightChoice === 'scissors') ||
			($leftChoice === 'paper' && $rightChoice === 'rock') ||
			($leftChoice === 'scissors' && $rightChoice === 'paper')
		){
			return 'winLeft';
		} else{
			return 'winRight';
		}
	}

    #[Route('/game/{id}', name: 'cancel_game', methods:['DELETE'])]
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

			}}
            return new JsonResponse('User not found', 401);
        }
    }

<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Cast\Object_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Validator\Constraints as Assert;
class GameController extends AbstractController
{
    #[Route('/games', name: 'get_list_of_games', methods:['GET'])]
    public function getPartyList(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(Game::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/games', name: 'create_game', methods:['POST'])]
    public function launchGame(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        
        $errorMessage = new JsonResponse('User not found', 401);

        if($currentUserId === null){

        }
        if(ctype_digit($currentUserId) === false){
            return $errorMessage;        
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        // Si l'utilisateur n'existe pas -> stop creation de partie
        if($currentUser === null){
            return $errorMessage;        }

        $new_party = new Game();
        $new_party->setState('pending');
        $new_party->setPlayerLeft($currentUser);

        $entityManager->persist($new_party);

        $entityManager->flush();

        return $this->json(
            $new_party,
            201,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{id}', name: 'fetch_game', methods:['GET'])]
    public function getGameInfo(EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $errorMessage = new JsonResponse('Game not found', 404);

        if(!ctype_digit($id)){
            return $errorMessage;
        }
        $party = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id]);
        if($party === null){
            return $errorMessage;
        }
        return $this->json(
            $party,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{id}/add/{playerRightId}', name: 'add_user_right', methods:['PATCH'])]
    public function inviteToGame(Request $request, EntityManagerInterface $entityManager, $id, $playerRightId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        $errorMessageGameNotFound = new JsonResponse('Game not found', 404);
        $errorMessageUserNotFound = new JsonResponse('User not found', 401);


        if(empty($currentUserId)){
            return new JsonResponse('User not found', 401);
        }

        if(!ctype_digit($id) && !ctype_digit($playerRightId) && !ctype_digit($currentUserId)){

            if(ctype_digit($currentUserId) === false){
                return new JsonResponse('User not found', 401);
            }
    
            return new JsonResponse('Game not found', 404);
        }

        $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);

        if($playerLeft === null){
            return new JsonResponse('User not found', 401);
        }

        $game = $entityManager->getRepository(Game::class)->find($id);

        if($game === null){
            return new JsonResponse('Game not found', 404);
        }

        if($game->getState() === 'ongoing' || $game->getState() === 'finished'){
            return new JsonResponse('Game already started', 409);
        }

 
        $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);

        if($playerRight === null){
            return new JsonResponse('User not found', 404);
        }
        if($playerLeft->getId() === $playerRight->getId()){
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

    public function gameResults(Game $game, string $choice, bool $userIsPlayerLeft) : Game{
        if($userIsPlayerLeft){
            $game->setPlayLeft($choice);
        }
        else{
            $game->setPlayRight($choice);
        }
        if(empty($game->getPlayLeft()) || empty($game->getPlayRight())){
            return $game;
        }

        if($game->getPlayLeft() === $game->getPlayRight()){
            $game->setResult("draw");
            $game->setState("finished");
            return $game;
        }

        if($game->getPlayLeft() === "rock" && $game->getPlayRight() === "scissors"){
            $game->setResult("winLeft");
            $game->setState("finished");
            return $game;
        }
        
        if($game->getPlayLeft() === "scissors" && $game->getPlayRight() === "paper"){
            $game->setResult("winLeft");
            $game->setState("finished");
            return $game;
        }

        if($game->getPlayLeft() === "paper" && $game->getPlayRight() === "rock"){
            $game->setResult("winLeft");
            $game->setState("finished");
            return $game;
        }
        $game->setResult("winRight");
        $game->setState("finished");
        return $game;
    }


    #[Route('/game/{id}', name: 'send_choice', methods:['PATCH'])]
    public function play(Request $request, EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if(ctype_digit($currentUserId) === false){
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if($currentUser === null){
            return new JsonResponse('User not found', 401);
        }
    
        if(ctype_digit($id) === false){
            return new JsonResponse('Game not found', 404);
        }

        $game = $entityManager->getRepository(Game::class)->find($id);

        if($game === null){
            return new JsonResponse('Game not found', 404);
        }

        $userIsPlayerLeft = false;
        $userIsPlayerRight = $userIsPlayerLeft;
        
        if($game->getPlayerLeft()->getId() === $currentUser->getId()){
            $userIsPlayerLeft = true;
        }elseif($game->getPlayerRight()->getId() === $currentUser->getId()){
            $userIsPlayerRight = true;
        }
        
        if(!$userIsPlayerLeft && !$userIsPlayerRight){
            return new JsonResponse('You are not a player of this game', 403);
        }

        // we must check the game is ongoing and the user is a player of this game
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

        if(!($form->isValid())){
            return new JsonResponse('Invalid choice', 400);
        }
        $data = $form->getData();

        if($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors'){
            return new JsonResponse('Invalid choice', 400);
        }
        
        $game = $this->gameResults($game, $data['choice'], $userIsPlayerLeft);
        $entityManager->flush();
        return $this->json(
            $game,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );

    }

    #[Route('/game/{id}', name: 'cancel_game', methods:['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {
   
        $currentUserId = $request->headers->get('X-User-Id');

        if(ctype_digit($currentUserId) === false){
            return new JsonResponse('User not found', 401);
        }
        $player = $entityManager->getRepository(User::class)->find($currentUserId);

        if($player === null){
            return new JsonResponse('User not found', 401);
        }

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
    }
}

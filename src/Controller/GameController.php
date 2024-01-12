<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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
    public function launchGame(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if($currentUserId == null){
            return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
        }

        if(ctype_digit($currentUserId) === false){
            return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if($currentUser === null){
            return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
        }

        $newGame = new Game();
        $newGame->setState('pending');
        $newGame->setPlayerLeft($currentUser);

        $entityManager->persist($newGame);

        $entityManager->flush();

        return $this->json(
            $newGame,
            JsonResponse::HTTP_CREATED,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/game/{identifier}', name: 'fetch_game', methods:['GET'])]
    public function getGameInfo(EntityManagerInterface $entityManager, $identifier): JsonResponse
    {
        if(ctype_digit($identifier)){
            $party = $entityManager->getRepository(Game::class)->findOneBy(['id' => $identifier]);

            if($party == null){
                return new JsonResponse('Game not found',JsonResponse::HTTP_NOT_FOUND);
            }
            return $this->json(
                $party,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
        return new JsonResponse('Game not found',JsonResponse::HTTP_NOT_FOUND);
    }

    #[Route('/game/{id}/add/{playerRightId}', name: 'add_user_right', methods:['PATCH'])]
    public function inviteToGame(Request $request, EntityManagerInterface $entityManager, $id, $playerRightId): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if(empty($currentUserId)){
            return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
        }
        
        if(ctype_digit($id) && ctype_digit($playerRightId) && ctype_digit($currentUserId)){
   
            $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);

            if($playerLeft === null){
                return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
            }

            $game = $entityManager->getRepository(Game::class)->find($id);

            if($game === null){
                return new JsonResponse('Game not found',JsonResponse::HTTP_NOT_FOUND);
            }

            if($game->getState() === 'ongoing' || $game->getState() === 'finished'){
                return new JsonResponse('Game already started', JsonResponse::HTTP_CONFLICT);
            }

 
            $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);

            if($playerRight == null){
                
                return new JsonResponse('User not found',JsonResponse::HTTP_NOT_FOUND);
            }

            if($playerLeft->getId() === $playerRight->getId()){
                return new JsonResponse('You can\'t play against yourself', JsonResponse::HTTP_CONFLICT);
            }
                
            $game->setPlayerRight($playerRight);
            $game->setState('ongoing');

            $entityManager->flush();

            return $this->json(
                $game,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
        if(ctype_digit($currentUserId) === false){
            return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
        }
            return new JsonResponse('Game not found',JsonResponse::HTTP_NOT_FOUND);
    }

    #[Route('/game/{identifier}', name: 'send_choice', methods:['PATCH'])]
    public function play(Request $request, EntityManagerInterface $entityManager, $identifier): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');

        if(ctype_digit($currentUserId) === false){
            return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if($currentUser === null){
            return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
        }
    
        if(ctype_digit($identifier) === false){
            return new JsonResponse('Game not found',JsonResponse::HTTP_NOT_FOUND);
        }

        $game = $entityManager->getRepository(Game::class)->find($identifier);

        if($game === null){
            return new JsonResponse('Game not found',JsonResponse::HTTP_NOT_FOUND);
        }

        $userIsPlayerLeft = false;
        $userIsPlayerRight = $userIsPlayerLeft;
        
        if($game->getPlayerLeft()->getId() === $currentUser->getId()){
            $userIsPlayerLeft = true;
        }elseif($game->getPlayerRight()->getId() === $currentUser->getId()){
            $userIsPlayerRight = true;
        }
        
        if(false === $userIsPlayerLeft && !$userIsPlayerRight){
            return new JsonResponse('You are not a player of this game',JsonResponse::HTTP_FORBIDDEN);
        }

        if($game->getState() === 'finished' || $game->getState() === 'pending'){
            return new JsonResponse('Game not started', JsonResponse::HTTP_CONFLICT);
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

        if($form->isValid()){

            $data = $form->getData();

            if($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors'){
                return new JsonResponse('Invalid choice', JsonResponse::HTTP_BAD_REQUEST);
            }

            if($userIsPlayerLeft){
                $game->setPlayLeft($data['choice']);
                $entityManager->flush();

                if($game->getPlayRight() !== null){
                        
                    switch($data['choice']){
                        case 'rock':
                            if($game->getPlayRight() === 'paper'){
                                $game->setResult('winRight');
                            }elseif($game->getPlayRight() === 'scissors'){
                                $game->setResult('winLeft');
                            }else{
                                $game->setResult('draw');
                            }
                            break;
                        case 'paper':
                            if($game->getPlayRight() === 'scissors'){
                                $game->setResult('winRight');
                            }elseif($game->getPlayRight() === 'rock'){
                                $game->setResult('winLeft');
                            }else{
                                $game->setResult('draw');
                            }
                            break;
                        case 'scissors':
                            if($game->getPlayRight() === 'rock'){
                                $game->setResult('winRight');
                            }elseif($game->getPlayRight() === 'paper'){
                                $game->setResult('winLeft');
                            }else{
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

            }elseif($userIsPlayerRight){            
                $game->setPlayRight($data['choice']);

                $entityManager->flush();

                if($game->getPlayLeft() !== null){

                    switch($data['choice']){
                        case 'rock':
                            if($game->getPlayLeft() === 'paper'){
                                $game->setResult('winLeft');
                            }elseif($game->getPlayLeft() === 'scissors'){
                                $game->setResult('winRight');
                            }else{
                                $game->setResult('draw');
                            }
                            break;
                        case 'paper':
                            if($game->getPlayLeft() === 'scissors'){
                                $game->setResult('winLeft');
                            }elseif($game->getPlayLeft() === 'rock'){
                                $game->setResult('winRight');
                            }else{
                                $game->setResult('draw');
                            }
                            break;
                        case 'scissors':
                            if($game->getPlayLeft() === 'rock'){
                                $game->setResult('winLeft');
                            }elseif($game->getPlayLeft() === 'paper'){
                                $game->setResult('winRight');
                            }else{
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

        }else{
            return new JsonResponse('Invalid choice', JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/game/{id}', name: 'cancel_game', methods:['DELETE'])]
    public function deleteGame(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {
   
        $currentUserId = $request->headers->get('X-User-Id');

        if(ctype_digit($currentUserId) === true){
            $player = $entityManager->getRepository(User::class)->find($currentUserId);

            if($player == null){
                return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
            }
            if(ctype_digit($id) === false){
                return new JsonResponse('Game not found',JsonResponse::HTTP_NOT_FOUND);
            }
        
            $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerLeft' => $player]);

            if(empty($game)){
                $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerRight' => $player]);
            }

            if(empty($game)){
                return new JsonResponse('Game not found', JsonResponse::HTTP_FORBIDDEN);
            }

            $entityManager->remove($game);
            $entityManager->flush();

            return new JsonResponse(null,JsonResponse::HTTP_NO_CONTENT);

        }else{
            return new JsonResponse('User not found', JsonResponse::HTTP_UNAUTHORIZED);
        }
    }
}

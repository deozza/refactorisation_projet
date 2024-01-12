<?php

namespace App\Controller\Game;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Validator\Constraints as Assert;

class playController extends AbstractController
{
    #[Route('/game/{identifiant}', name: 'send_choice', methods:['PATCH'])]
    public function playGame(Request $request, EntityManagerInterface $entityManager, $identifiant): JsonResponse
    {
        $messageErreur = 'User not found';
        $messageErreurTwo = 'Game not found';
        $currentUserId = $request->headers->get('X-User-Id');

        if(ctype_digit($currentUserId) === false){
            return new JsonResponse($messageErreur, 401);
        }

        $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);

        if($currentUser === null){
            return new JsonResponse($messageErreur, 401);
        }
    
        if(ctype_digit($identifiant) === false){
            return new JsonResponse($messageErreurTwo, 404);
        }

        $game = $entityManager->getRepository(Game::class)->find($identifiant);

        if($game === null){
            return new JsonResponse($messageErreurTwo, 404);
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

        if($form->isValid()){

            $data = $form->getData();

            // on joue avec les règles de base de pierre feuille ciseaux
            if($data['choice'] !== 'rock' && $data['choice'] !== 'paper' && $data['choice'] !== 'scissors'){
                return new JsonResponse('Invalid choice', 400);
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
            return new JsonResponse('Invalid choice', 400);
        }

        return new JsonResponse('coucou');
    }
}
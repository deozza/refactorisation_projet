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

class gameToInviteController extends AbstractController
{

    #[Route('/game/{id}/add/{playerRightId}', name: 'add_user_right', methods:['PATCH'])]
    public function inviteToGame(Request $request, EntityManagerInterface $entityManager, $id, $playerRightId): JsonResponse
    {
        $messageErreur = 'User not found';
        $messageErreurTwo = 'Game not found';
        $currentUserId = $request->headers->get('X-User-Id');

        if(empty($currentUserId)){
            return new JsonResponse($messageErreur, 401);
        }

        if(!ctype_digit($id) && !ctype_digit($playerRightId) && !ctype_digit($currentUserId)){
            if(ctype_digit($currentUserId) === false){
                return new JsonResponse($messageErreur, 401);
            }
            return new JsonResponse($messageErreurTwo, 404);
        }else{
            $playerLeft = $entityManager->getRepository(User::class)->find($currentUserId);
        
            if($playerLeft === null){
                return new JsonResponse($messageErreur, 401);
            }
            $game = $entityManager->getRepository(Game::class)->find($id);
        
            if($game === null){
                return new JsonResponse($messageErreurTwo, 404);
            }
            if($game->getState() === 'ongoing' || $game->getState() === 'finished'){
                return new JsonResponse('Game already started', 409);
            }
        
            $playerRight = $entityManager->getRepository(User::class)->find($playerRightId);
            if($playerRight === null){
                return new JsonResponse($messageErreur, 404);
        
            }else{
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
        }
    }
}
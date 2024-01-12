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

class gameDeleteController extends AbstractController
{
    #[Route('/game/{id}', name: 'annuler_game', methods:['DELETE'])]
    public function deleteGameController(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {
        $messageErreur = 'User not found';
        $messageErreurTwo = 'Game not found';
        $currentUserId = $request->headers->get('X-User-Id');

        if(ctype_digit($currentUserId) === true){
            $player = $entityManager->getRepository(User::class)->find($currentUserId);

            if($player !== null){

                if(ctype_digit($id) === false){
                    return new JsonResponse($messageErreurTwo, 404);
                }
        
                $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerLeft' => $player]);

                if(empty($game)){
                    $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $id, 'playerRight' => $player]);
                }

                if(empty($game)){
                    return new JsonResponse($messageErreurTwo, 403);
                }

                $entityManager->remove($game);
                $entityManager->flush();

                return new JsonResponse(null, 204);

            }else{
                return new JsonResponse($messageErreur, 401);
            }
        }else{
            return new JsonResponse($messageErreur, 401);
        }
    }
}

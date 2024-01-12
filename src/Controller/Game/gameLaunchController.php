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

class gameLaunchController extends AbstractController
{
    #[Route('/games', name: 'create_game', methods:['POST'])]
    public function launchGame(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        $messageErreur = 'User not found';

        if($currentUserId === null){
            return new JsonResponse($messageErreur, 401);
        }
        else{
            if(ctype_digit($currentUserId) === false){
                return new JsonResponse($messageErreur, 401);
            }
            $currentUser = $entityManager->getRepository(User::class)->find($currentUserId);
        
            // Si l'utilisateur n'existe pas -> stop creation de partie
            if($currentUser === null){
                return new JsonResponse($messageErreur, 401);
            }
            $nouvelle_partie = new Game();
            $nouvelle_partie->setState('pending');
            $nouvelle_partie->setPlayerLeft($currentUser);
            $entityManager->persist($nouvelle_partie);
            $entityManager->flush();
            return $this->json(
                $nouvelle_partie,
                201,
                headers: ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }
}

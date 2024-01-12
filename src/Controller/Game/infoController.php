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

class infoController extends AbstractController
{
    #[Route('/game/{identifiant}', name: 'fetch_game', methods:['GET'])]
    public function getGameInfo(EntityManagerInterface $entityManager, $identifiant): JsonResponse
    {
        $messageErreurTwo = 'Game not found';
        if(!ctype_digit($identifiant)){
            return new JsonResponse($messageErreurTwo, 404);
        }
        $party = $entityManager->getRepository(Game::class)->findOneBy(['id' => $identifiant]);
            
        if($party === null){
            return new JsonResponse($messageErreurTwo, 404);
        }
        return $this->json(
            $party,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }
}
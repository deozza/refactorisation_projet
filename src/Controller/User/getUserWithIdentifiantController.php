<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class getUserWithIdentifiantController extends AbstractController
{
    #[Route('/user/{identifiant}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserWithIdentifiant($identifiant, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!ctype_digit($identifiant)) {
            return new JsonResponse('Wrong id', 404);
        }

        $joueur = $entityManager->getRepository(User::class)->find($identifiant);

        if (!$joueur) {
            return new JsonResponse('Wrong id', 404);
        }

        return new JsonResponse([
            'name' => $joueur->getName(),
            'age' => $joueur->getAge(),
            'id' => $joueur->getId()
        ], 200);
    }
}

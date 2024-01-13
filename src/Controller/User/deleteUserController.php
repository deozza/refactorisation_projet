<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class deleteUserController extends AbstractController
{
    #[Route('/user/{id}', name: 'delete_user_by_identifiant', methods:['DELETE'])]
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $joueur = $entityManager->getRepository(User::class)->find($id);

        if (!$joueur) {
            return new JsonResponse('Wrong id', 404);
        }

        try {
            $entityManager->remove($joueur);
            $entityManager->flush();

            $existeEncore = $entityManager->getRepository(User::class)->find($id);

            if ($existeEncore) {
                throw new \Exception("Le user n'a pas été supprimé");
            } else {
                return new JsonResponse('', 204);
            }
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }
}

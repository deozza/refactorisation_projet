<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class UserController extends AbstractController
{

    #[Route('/user/{id}', name: 'delete_user_by_id', methods:['DELETE'])]
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
        if(count($player) == 1) {
            try{
                $entityManager->remove($player[0]);
                $entityManager->flush();

                $stillExists = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
    
                if(!empty($stillExists)){
                    throw new \Exception("Le user n'a pas éte délété");
                    return null;
                }else{
                    return new JsonResponse('', 204);
                }
            } catch(\Exception $e) {
                return new JsonResponse($e->getMessage(), 500);
            }
        } else {
            return new JsonResponse('Wrong id', 404);
        }    
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class UserController extends AbstractController
{


    #[Route('/user/{id}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserWithId($id, EntityManagerInterface $entityManager): JsonResponse
    {
        if(ctype_digit($id)){
            $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
            if(count($player) == 1){
                return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
            }else{
                return new JsonResponse('Wrong id', 404);
            }
        }
        return new JsonResponse('Wrong id', 404);
    }

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

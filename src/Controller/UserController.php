<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\CreateUserType;
use App\Form\UpdateUserType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    #[Route('/users', name: 'users-list', methods:['GET'])]
    public function getListUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'user_post', methods:['POST'])]
    public function createUser(Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        if('POST' !== $request->getMethod()){ 
            return new JsonResponse(
                'Wrong method',
                Response::HTTP_METHOD_NOT_ALLOWED
            );
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(CreateUserType::class);
        $form->submit($data);
        if(!$form->isValid()){ 
            return new JsonResponse(
                'Invalid form',
                Response::HTTP_BAD_REQUEST
            );
        }
        if($data['age'] <= 21){ 
            return new JsonResponse(
                'Wrong age',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
        if($user){ 
            return new JsonResponse(
                'Name already exists',
                Response::HTTP_BAD_REQUEST,
            );
        }

        $player = new User();
        $player->setName($data['nom']);
        $player->setAge($data['age']);
        $entityManager->persist($player);
        $entityManager->flush();

        return $this->json(
                    $player,
                    Response::HTTP_CREATED,
                    ['Content-Type' => 'application/json;charset=UTF-8']
                );                    
    }

    #[Route('/user/{userId}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserWithUserId($userId, EntityManagerInterface $entityManager): JsonResponse
    {
        if(!ctype_digit($userId)){ 
            return new JsonResponse(
                'Wrong id',
                Response::HTTP_NOT_FOUND
            );
        }

        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$userId]);
        if(!$player) { 
            return new JsonResponse(
                'Wrong id',
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse(
            array(
                'name'=>$player[0]->getName(),
                "age"=>$player[0]->getAge(),
                'id'=>$player[0]->getId()),
                Response::HTTP_OK,
        );
    }

    #[Route('/user/{userId}', name: 'udpate_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $userId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if('PATCH' !== $request->getMethod()){
            return new JsonResponse(
                'Wrong method',
                Response::HTTP_METHOD_NOT_ALLOWED
            );
        }

        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$userId]);
        if(!$player){ 
            return new JsonResponse(
                'Wrong id',
                Response::HTTP_NOT_FOUND
            );
        }

        $form = $this->createForm(UpdateUserType::class);
        $form->submit($data);
        if(!$form->isValid()){ 
            return new JsonResponse(
                'Invalid form',
                Response::HTTP_BAD_REQUEST
            );
        }
        
        foreach($data as $key=>$value){
            switch($key){
                case 'nom':
                    $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                    if($user){ 
                        return new JsonResponse(
                            'Name already exists',
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                    $player[0]->setName($data['nom']);
                    $entityManager->flush();
                    break;
                case 'age':
                    if($data['age'] <= 21){ 
                        return new JsonResponse(
                            'Wrong age',
                            Response::HTTP_BAD_REQUEST
                        );
                    }
                    $player[0]->setAge($data['age']);
                    $entityManager->flush();
                    break;
            }
        }   
        
        return new JsonResponse(
            array('name'=>$player[0]->getName(),
            "age"=>$player[0]->getAge(),
            'id'=>$player[0]->getId()),
            Response::HTTP_OK,
        );
    }

    #[Route('/user/{id}', name: 'delete_user_by_id', methods:['DELETE'])]
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
        if(count($player) !== 1) { 
            return new JsonResponse(
                'Wrong id',
                Response::HTTP_NOT_FOUND
            );
        }

        try{
            $entityManager->remove($player[0]);
            $entityManager->flush();

            $alreadyExists = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

            if(empty($alreadyExists)){ 
                return new JsonResponse(
                    "",
                    Response::HTTP_NO_CONTENT,
                );
            }
            if(!empty($alreadyExists)){ 
                throw new \Exception("Le user n'a pas éte délété");
                return null;
            }
                throw new \Exception("User not deleted");
                return null;
        }catch(\Exception $e){
            return new JsonResponse( 
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
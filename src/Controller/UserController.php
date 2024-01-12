<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\CreateUserType;
use App\Form\UpdateUserType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    #[Route('/users', name: 'users-list', methods:['GET'])]
    public function getListUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            Response::HTTP_OK,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'user_post', methods:['POST'])]
    public function createUser(Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(CreateUserType::class);
        $form->submit($data);

        if(!$form->isValid()){
            return new JsonResponse(
                'Invalid form',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
        
        if(0 !== count($user)){
            return new JsonResponse(
                'You need to have a user',
                Response::HTTP_BAD_REQUEST,
            );
        }
        if($data['age'] <= 21){
            return new JsonResponse(
                'Wrong age',
                Response::HTTP_BAD_REQUEST
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

        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$userId]);

        if(0 === count($player)) {
            return new JsonResponse(
                'User not found',
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
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$userId]);
        $data = json_decode($request->getContent(), true);

        if(0 === count($player)){
            return new JsonResponse(
                'User not found',
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
                    if(count($user) !== 0){
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
                'User not found',
                Response::HTTP_NOT_FOUND
            );
        }

        try{
            $entityManager->remove($player[0]);
            $entityManager->flush();

            $alreadyExists = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

            if(empty($alreadyExists)){
                return new JsonResponse(
                    "No content",
                    Response::HTTP_NO_CONTENT,
                );
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
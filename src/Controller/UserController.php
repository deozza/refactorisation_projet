<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

use App\Form\Type\CreateUserType;
use App\Form\Type\UpdateUserType;

class UserController extends AbstractController
{
    #[Route('/users', name: 'list_users', methods:['GET'])]
    public function listUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'create_user', methods:['POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if($request->getMethod() === 'POST'){
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(CreateUserType::class, $user);

            $form->submit($data);

            if($form->isValid())
            {
                if($data['age'] > 21){
                    $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                    if(!$user){
                        $newUser = new User();
                        $newUser->setName($data['nom']);
                        $newUser->setAge($data['age']);
                        $entityManager->persist($newUser);
                        $entityManager->flush();

                        return $this->json(
                                    $newUser,
                                    201,
                                    ['Content-Type' => 'application/json;charset=UTF-8']
                                );                    
                    }else{
                        return new JsonResponse('Name already exists', 400);
                    }
                }else{
                    return new JsonResponse('Wrong age', 400);
                }
            }else{
                return new JsonResponse('Invalid form', 400);
            }
        }else{
            return new JsonResponse('Wrong method', 405);
        }
    }

    #[Route('/user/{id}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserById($id, EntityManagerInterface $entityManager): JsonResponse
    {
        if(ctype_digit($id)){
            $user = $entityManager->getRepository(User::class)->find($id);
            if($user){
                return new JsonResponse(array('name'=>$user->getName(), "age"=>$user->getAge(), 'id'=>$user->getId()), 200);
            }else{
                return new JsonResponse('User not found', 404);
            }
        }
        return new JsonResponse('Invalid ID', 400);
    }

    #[Route('/user/{id}', name: 'udpate_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $id, Request $request): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if($user){
            if($request->getMethod() == 'PATCH'){
                $data = json_decode($request->getContent(), true);

                $form = $this->createForm(UpdateUserType::class, $user);

                $form->submit($data);

                if($form->isValid()) {
                    foreach($data as $key=>$value){
                        switch($key){
                            case 'nom':
                                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['name'=>$data['nom']]);
                                if(!$existingUser || $existingUser === $user){
                                    $user->setName($data['nom']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Name already exists', 400);
                                }
                                break;
                            case 'age':
                                if($data['age'] > 21){
                                    $user->setAge($data['age']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Wrong age', 400);
                                }
                                break;
                        }
                    }
                }else{
                    return new JsonResponse('Invalid form', 400);
                }
            }else{
                $data = json_decode($request->getContent(), true);
                return new JsonResponse('Wrong method', 405);
            }

            return new JsonResponse(array('name'=>$user->getName(), "age"=>$user->getAge(), 'id'=>$user->getId()), 200);
        }else{
            return new JsonResponse('Wrong id', 404);
        }    
    }

    #[Route('/user/{id}', name: 'delete_user_by_id', methods:['DELETE'])]
    public function deleteUserById($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if($user){
            try{
                $entityManager->remove($user);
                $entityManager->flush();

                $userStillExists = $entityManager->getRepository(User::class)->find($id);
    
                if($userStillExists){
                    throw new \Exception('The user was not deleted');
                    return null;
                }else{
                    return new JsonResponse('', 204);
                }
            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 500);
            }
        }else{
            return new JsonResponse('User not found', 404);
        }    
    }
}

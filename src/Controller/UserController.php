<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use PDO;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

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
        if($request->getMethod() !== 'POST') {
            return new JsonResponse('Wrong method', 405);
        }


        $data = json_decode($request->getContent(), true);
        $form = $this->createFormBuilder()
            ->add('nom', TextType::class, [
                'constraints'=>[
                    new Assert\NotBlank(),
                    new Assert\Length(['min'=>1, 'max'=>255])
                ]
            ])
            ->add('age', NumberType::class, [
                'constraints'=>[
                    new Assert\NotBlank()
                ]
            ])
            ->getForm();

        $form->submit($data);

        if(!$form->isValid()){
            return new JsonResponse('Invalid form', 400);
        }

        if($form->isValid())
        {
            $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
            
            if(count($user) !== 0){
                return new JsonResponse('Name already exists', 400);
            }

            if($data['age'] <= 21){
                return new JsonResponse('Wrong age', 400);
            }

            if($data['age'] > 21){
                    $player = new User();
                    $player->setName($data['nom']);
                    $player->setAge($data['age']);
                    $entityManager->persist($player);
                    $entityManager->flush();

                    return $this->json(
                                $player,
                                201,
                                ['Content-Type' => 'application/json;charset=UTF-8']
                            );                    
            }
        }
        
    }

    #[Route('/user/{userId}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserWithUserId($userId, EntityManagerInterface $entityManager): JsonResponse
    {
        if(!ctype_digit($userId)){
            return new JsonResponse('Wrong id', 404);
        }

        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$userId]);

        if(count($player) !== 1) {
            return new JsonResponse('Wrong id', 404);
        }

        if(count($player) === 1){
            return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
        }
        
    }

    #[Route('/user/{userId}', name: 'udpate_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $userId, Request $request): JsonResponse
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$userId]);

        if(count($player) !== 1){
            return new JsonResponse('Wrong id', 404);
        }

        if(count($player) === 1){

            if($request->getMethod() !== 'PATCH'){
                $data = json_decode($request->getContent(), true);
                return new JsonResponse('Wrong method', 405);
            }

            if($request->getMethod() === 'PATCH'){
                $data = json_decode($request->getContent(), true);
                $form = $this->createFormBuilder()
                    ->add('nom', TextType::class, array(
                        'required'=>false
                    ))
                    ->add('age', NumberType::class, [
                        'required' => false
                    ])
                    ->getForm();

                $form->submit($data);

                if(!$form->isValid()){
                    return new JsonResponse('Invalid form', 400);
                }
                
                foreach($data as $key=>$value){
                    switch($key){
                        case 'nom':
                            $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                            if(count($user) !== 0){
                                return new JsonResponse('Name already exists', 400);
                            }
                            if(count($user) === 0){
                                $player[0]->setName($data['nom']);
                                $entityManager->flush();
                            }
                            break;
                        case 'age':
                            if($data['age'] <= 21){
                                return new JsonResponse('Wrong age', 400);
                            }
                            if($data['age'] > 21){
                                $player[0]->setAge($data['age']);
                                $entityManager->flush();
                            }
                            break;
                    }
                }
                
            }

            return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
        }  
    }

    #[Route('/user/{id}', name: 'delete_user_by_id', methods:['DELETE'])]
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

        if(count($player) !== 1) {
            return new JsonResponse('Wrong id', 404);
        }
        
        if(count($player) === 1){
            try{
                $entityManager->remove($player[0]);
                $entityManager->flush();

                $alreadyExists = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
    
                if(empty($alreadyExists)){
                    return new JsonResponse('', 204);
                }
                if(!empty($alreadyExists)){
                    throw new \Exception("User not deleted");
                    return null;
                }
            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 500);
            }
        } 
    }
}
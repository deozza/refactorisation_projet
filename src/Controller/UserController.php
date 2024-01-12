<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends AbstractController
{
    #[Route('/users', name: 'users_list', methods:['GET'])]
    public function getUsersList(EntityManagerInterface $entityManager): JsonResponse
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
        if($request->getMethod() !== 'POST'){
            return new JsonResponse('Wrong method', JsonResponse::HTTP_METHOD_NOT_ALLOWED);
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

        if($form->isValid())
        {
            if($data['age'] < 21){
                return new JsonResponse('Wrong age', JsonResponse::HTTP_BAD_REQUEST);
            }
            $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
            if(count($user) !== 0){
                return new JsonResponse('Name already exists', JsonResponse::HTTP_BAD_REQUEST);
            }
            $player = new User();
            $player->setName($data['nom']);
            $player->setAge($data['age']);
            $entityManager->persist($player);
            $entityManager->flush();

            return $this->json(
                        $player,
                        JsonResponse::HTTP_CREATED,
                        ['Content-Type' => 'application/json;charset=UTF-8']
                    );                    
        }else{
            return new JsonResponse('Invalid form', JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/user/{identifiant}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserById($identifiant, EntityManagerInterface $entityManager): JsonResponse
    {
        if(ctype_digit($identifiant)){
            $player = $entityManager->getRepository(User::class)->findBy(['id'=>$identifiant]);
            if(count($player) !== 1){
                return new JsonResponse('Wrong id', JsonResponse::HTTP_NOT_FOUND);
            }
            return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), JsonResponse::HTTP_OK); 
        }
        return new JsonResponse('Wrong id', JsonResponse::HTTP_NOT_FOUND);
    }

    #[Route('/user/{identifiant}', name: 'udpate_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$identifiant]);


        if(count($player) !== 1){
            return new JsonResponse('Wrong id', JsonResponse::HTTP_NOT_FOUND);
        }
        if($request->getMethod() !== 'PATCH'){
            $data = json_decode($request->getContent(), true);
            return new JsonResponse('Wrong method', JsonResponse::HTTP_METHOD_NOT_ALLOWED);
        }
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
        if($form->isValid()) {

            foreach($data as $key=>$value){
                switch($key){
                    case 'nom':
                        $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                        if(count($user) !== 0){
                            return new JsonResponse('Name already exists', JsonResponse::HTTP_BAD_REQUEST);
                        }
                        $player[0]->setName($data['nom']);
                        $entityManager->flush();
                        break;
                    case 'age':
                        if($data['age'] < 21){
                            return new JsonResponse('Wrong age', JsonResponse::HTTP_BAD_REQUEST);
                        }
                        $player[0]->setAge($data['age']);
                        $entityManager->flush();
                        break;
                }
            }
            }else{
                return new JsonResponse('Invalid form', JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), JsonResponse::HTTP_OK);  
    }

    #[Route('/user/{id}', name: 'delete_user_by_identifiant', methods:['DELETE'])]
    public function deleteUserById($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
        if(count($player) !== 1){
            return new JsonResponse('Wrong id', JsonResponse::HTTP_NOT_FOUND);
        }
        try{
            $entityManager->remove($player[0]);
            $entityManager->flush();

            $existeEncore = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
    
            if(empty($existeEncore)){
                return new JsonResponse('', JsonResponse::HTTP_NO_CONTENT);
            }
            throw new \Exception("Le user n'a pas éte délété");
            return null;
                
        }catch(\Exception $e){
            return new JsonResponse($e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        } 
    }
}

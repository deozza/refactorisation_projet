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

    const AGE_MAJORITY = 21;

    #[Route('/users', name: 'get_users', methods:['GET'])]
    public function getUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'post_user', methods:['POST'])]
    public function createUser(Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        if ($request->getMethod() !== 'POST'){
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

        if (!$form->isValid()){
            return new JsonResponse('Invalid form', 400);
        }

        if ($data['age'] <= self::AGE_MAJORITY){
            return new JsonResponse('Wrong age', 400);
        }

        $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);

        if (count($user) !== 0){
            return new JsonResponse('Name already exists', 400);
        }

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

    #[Route('/user/{id}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserById($id, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!ctype_digit($id)){
            return new JsonResponse('Wrong id', 404);
        }

        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

        if (count($player) != 1){
            return new JsonResponse('Wrong id', 404);
        }

        return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
    }

    #[Route('/user/{id}', name: 'udpate_user_by_id', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $id, Request $request): JsonResponse
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

        if (count($player) != 1){
            return new JsonResponse('Wrong id', 404);
        }

        if ($request->getMethod() != 'PATCH'){
            return new JsonResponse('Wrong method', 405);
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

        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        foreach($data as $key=>$value){
            switch($key){
                case 'nom':
                    $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                    if (count($user) !== 0){
                        return new JsonResponse('Name already exists', 400);
                    }

                    $player[0]->setName($data['nom']);
                    $entityManager->flush();
                    break;

                case 'age':
                    if ($data['age'] <= self::AGE_MAJORITY){
                        return new JsonResponse('Wrong age', 400);
                    }

                    $player[0]->setAge($data['age']);
                    $entityManager->flush();
                    break;
            }
        }

        return new JsonResponse(array('name'=>$player[0]->getName(), "age"=>$player[0]->getAge(), 'id'=>$player[0]->getId()), 200);
    }

    #[Route('/user/{id}', name: 'delete_user_by_id', methods:['DELETE'])]
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

        if (count($player) != 1){
            return new JsonResponse('Wrong id', 404);
        }

        try {
            $entityManager->remove($player[0]);
            $entityManager->flush();

            $isUserDeleted = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

            if (empty($isUserDeleted)){
                return new JsonResponse('', 204);
            }

            throw new \Exception("Le user n'a pas éte délété");
            return null;

        } catch(\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }
}

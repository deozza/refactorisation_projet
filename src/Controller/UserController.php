<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class UserController extends AbstractController
{
    #[Route('/users', name: 'list_users', methods:['GET'])]
    public function getListOfUsers(EntityManagerInterface $entityManager): JsonResponse
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
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse('Wrong method', 405);
        }

        $data = json_decode($request->getContent(), true);
        
        $form = $this->createForm(UserType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        if ($data['age'] <= 21) {
            return new JsonResponse('Wrong age', 400);
        }

        $user = $entityManager->getRepository(User::class)->findBy(['name' => $data['nom']]);
        if ($user) {
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

    #[Route('/user/{id}', name: 'get_user', methods:['GET'])]
    public function getUserById($id, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!ctype_digit($id)) {
            return new JsonResponse('Invalid Id', 404);
        }

        $player = $entityManager->getRepository(User::class)->find($id);

        if (!$player) {
            return new JsonResponse('User not found', 404);
        }

        return new JsonResponse([
            'name' => $player->getName(), 
            'age' => $player->getAge(), 
            'id' => $player->getId()
        ], 200);
    }

    #[Route('/user/{id}', name: 'update_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $id, Request $request): JsonResponse
    {
        if ($request->getMethod() !== 'PATCH') {
            return new JsonResponse('Wrong method', 405);
        }

        $player = $entityManager->getRepository(User::class)->find($id);
        if (!$player) {
            return new JsonResponse('User not found', 404);
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(UserType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'nom':
                    $userWithName = $entityManager->getRepository(User::class)->findBy(['name' => $value]);
                    if ($userWithName && $userWithName !== $player) {
                        return new JsonResponse('Name already exists', 400);
                    }
                    $player->setName($value);
                    break;
                case 'age':
                    if ($value <= 21) {
                        return new JsonResponse('Wrong age', 400);
                    }
                    $player->setAge($value);
                    break;
            }
        }

        $entityManager->flush();

        return new JsonResponse([
            'name' => $player->getName(), 
            'age' => $player->getAge(), 
            'id' => $player->getId()
        ], 200);
    }

    #[Route('/user/{id}', name: 'delete_user', methods:['DELETE'])]
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $player = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
        if(!$player){
            return new JsonResponse('Wrong id', 404);
        }

        $entityManager->remove($player[0]);
        $entityManager->flush();

        $userStillExists = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

        if(!$userStillExists){
            return new JsonResponse('', 204);
        }
        throw new \Exception('The user was not deleted');
        return null;
    }
}
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
    #[Route('/users', name: 'liste_des_users', methods:['GET'])]
    public function getUsersList(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );

    }

    #[Route('/users', name: 'user_post', methods:['POST'])]
public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    if (!$request->isMethod('POST')) {
        return $this->json('Wrong method', 405);
    }

    $form = $this->createForm(CreateUserType::class);
    $form->handleRequest($request);

    if (!$form->isSubmitted() || !$form->isValid()) {
        return $this->json('Invalid form', 400);
    }


    $userData = $form->getData();

    if ($userData->getAge() > 21) {
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['name' => $userData->getNom()]);

        if (!$existingUser) {
            $user = new User();
            $user->setName($userData->getNom());
            $user->setAge($userData->getAge());

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json($user, 201, ['Content-Type' => 'application/json;charset=UTF-8']);
        } else {
            return $this->json('Name already exists', 400);
        }
    } else {
        return $this->json('Wrong age', 400);
    }
}
   

    #[Route('/user/{identifiant}', name: 'get_user_with_id', methods:['GET'])]
   public function getUserWithIdentifiant($identifiant, EntityManagerInterface $entityManager): JsonResponse

    {
        if (!ctype_digit($identifiant)) {
            return new JsonResponse('Wrong id', 404);
        }

        $joueur = $entityManager->getRepository(User::class)->findOneBy(['id' => $identifiant]);

        if ($joueur) {
            $userData = [
                'name' => $joueur->getName(),
                'age' => $joueur->getAge(),
                'id' => $joueur->getId(),
            ];

            return $this->json($userData, 200, ['Content-Type' => 'application/json;charset=UTF-8']);
        }

        return new JsonResponse('Wrong id', 404);
    }


    #[Route('/user/{identifiant}', name: 'verif_user', methods:['PATCH'])]
public function verifUser(EntityManagerInterface $entityManager, int $identifiant, Request $request): JsonResponse
{
    $user = $entityManager->getRepository(User::class)->find($identifiant);

    if (!$user) {
        return $this->json('Wrong id', 404);
    }

    if (!$request->isMethod('PATCH')) {
        return $this->json('Wrong method', 405);
    }

    $form = $this->createForm(VerifUserType::class, $user, ['method' => 'PATCH']);
    $form->handleRequest($request);

    if (!$form->isSubmitted() || !$form->isValid()) {
        return $this->json('Invalid form', 400);
    }

    $entityManager->flush();

    return $this->json([
        'name' => $user->getName(),
        'age' => $user->getAge(),
        'id' => $user->getId()
    ], 200);
}

    #[Route('/user/{userId}', name: 'delete_user_by_id', methods:['DELETE'], requirements:['userId' => '\d+'])]
    public function deleteUserById(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($userId);
    
        if (!$user) {
            return new JsonResponse('Wrong id', 404);
        }
    
        try {
            $entityManager->remove($user);
            $entityManager->flush();
    
            $exists = $entityManager->getRepository(User::class)->find($userId);
    
            if ($exists) {
                throw new \Exception("Le user n'a pas été supprimé");
            }
    
            return new JsonResponse('', 204);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }
    
    private function isUsernameExists(string $username, EntityManagerInterface $entityManager): bool
    {
        $user = $entityManager->getRepository(User::class)->findBy(['name' => $username]);
        return count($user) > 0;
    }
    
}

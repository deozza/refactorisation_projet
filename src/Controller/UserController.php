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
    #[Route('/user/{identifiant}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserWithIdentifiant($identifiant, EntityManagerInterface $entityManager): JsonResponse
    {
        if(ctype_digit($identifiant)){
            $joueur = $entityManager->getRepository(User::class)->findBy(['id'=>$identifiant]);
            if(count($joueur) == 1){
                return new JsonResponse(array('name'=>$joueur[0]->getName(), "age"=>$joueur[0]->getAge(), 'id'=>$joueur[0]->getId()), 200);
            }else{
                return new JsonResponse('Wrong id', 404);
            }
        }
        return new JsonResponse('Wrong id', 404);
    }

    

    #[Route('/user/{identifiant}', name: 'udpate_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
    {
        $joueur = $entityManager->getRepository(User::class)->findBy(['id' => $identifiant]);

        if (count($joueur) !== 1) {
            return new JsonResponse('Wrong id', 404);
        }

        if ($request->getMethod() !== 'PATCH') {
            return new JsonResponse('Wrong method', 405);
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createFormBuilder()
            ->add('nom', TextType::class, ['required' => false])
            ->add('age', NumberType::class, ['required' => false])
            ->getForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'nom':
                    $user = $entityManager->getRepository(User::class)->findBy(['name' => $data['nom']]);
                    if (count($user) === 0) {
                        $joueur[0]->setName($data['nom']);
                        $entityManager->flush();
                    } else {
                        return new JsonResponse('Name already exists', 400);
                    }
                    break;
                case 'age':
                    if ($data['age'] > 21) {
                        $joueur[0]->setAge($data['age']);
                        $entityManager->flush();
                    } else {
                        return new JsonResponse('Wrong age', 400);
                    }
                    break;
            }
        }

        return new JsonResponse(['name' => $joueur[0]->getName(), 'age' => $joueur[0]->getAge(), 'id' => $joueur[0]->getId()], 200);
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

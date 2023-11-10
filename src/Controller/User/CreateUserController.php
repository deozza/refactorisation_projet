<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\UserFormType;
use Symfony\Component\HttpFoundation\Request;

class CreateUserController extends AbstractController
{
    #[Route('/users', name: 'user_post', methods: ['POST'])]
    /**
     * Creates a new user based on the data provided in the request.
     *
     * @param Request $request The HTTP request object.
     * @param EntityManagerInterface $entityManager The entity manager for database interaction.
     *
     * @return JsonResponse A JSON response indicating the success or failure of the user creation.
     */
    public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Check if the request method is POST
        if (!$request->isMethod('POST')) {
            return new JsonResponse('Wrong method', 405);
        }

        // Decode JSON content from the request
        $data = json_decode($request->getContent(), true);

        // Create form and handle request
        $form = $this->createForm(UserFormType::class);
        $form->submit($data);

        // Validate the form
        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        // Validate age
        if ($data['age'] <= 21) {
            return new JsonResponse('Wrong age', 400);
        }

        // Check if the name already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['name' => $data['nom']]);
        if ($existingUser) {
            return new JsonResponse('Name already exists', 400);
        }

        // Create and persist the new user
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
    }
}
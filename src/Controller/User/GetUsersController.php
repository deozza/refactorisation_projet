<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class GetUsersController extends AbstractController
{
    #[Route('/users', name: 'liste_des_users', methods:['GET'])]
    /**
     * Retrieves a list of users from the database and returns it as a JSON response.
     *
     * @param EntityManagerInterface $entityManager The entity manager to interact with the database.
     *
     * @return JsonResponse A JSON response containing the list of users.
     */
    public function getListOfUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        // Get the repository for the User entity
        $userRepository = $entityManager->getRepository(User::class);

        // Fetch all users from the database
        $users = $userRepository->findAll();

        // Return the list of users as a JSON response
        return $this->json($users);
    }
}

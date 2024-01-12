<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class GetUserByIdController extends AbstractController
{
    #[Route('/user/{identifier}', name: 'get_user_by_id', methods:['GET'])]
    /**
     * Retrieves a user based on their identifier and returns a JsonResponse.
     *
     * @param string $identifier The user identifier to search for.
     * @param EntityManagerInterface $entityManager The entity manager to interact with the database.
     *
     * @return JsonResponse A JSON response containing the user data if found, or a 404 Not Found response.
     */
    public function getUserWithidentifier($identifier, EntityManagerInterface $entityManager): JsonResponse
    {
        // Validate if $identifier is a digit
        if (!ctype_digit($identifier)) {
            return $this->createNotFoundResponse();
        }

        // Find the user by id
        $user = $entityManager->getRepository(User::class)->find($identifier);

        // Check if the user exists
        if (!$user) {
            return $this->createNotFoundResponse();
        }

        // Create and return the JsonResponse
        return $this->createUserJsonResponse($user);
    }

    /**
     * Create a JsonResponse for a user.
     *
     * @param User $user
     * @return JsonResponse
     */
    private function createUserJsonResponse(User $user): JsonResponse
    {
        $userData = [
            'name' => $user->getName(),
            'age' => $user->getAge(),
            'id' => $user->getId(),
        ];

        return new JsonResponse($userData, JsonResponse::HTTP_OK);
    }

    /**
     * Create a JsonResponse for a not found response.
     *
     * @return JsonResponse
     */
    private function createNotFoundResponse(): JsonResponse
    {
        return new JsonResponse('User not found', JsonResponse::HTTP_NOT_FOUND);
    }
}


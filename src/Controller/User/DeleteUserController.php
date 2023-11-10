<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class DeleteUserController extends AbstractController
{
    #[Route('/user/{id}', name: 'delete_user_by_identifiant', methods: ['DELETE'])]
    /**
     * Deletes a user by the given ID.
     *
     * @param int $id The ID of the user to be deleted.
     * @param EntityManagerInterface $entityManager The entity manager responsible for database operations.
     *
     * @return JsonResponse A JSON response indicating the result of the deletion operation.
     */
    public function deleteUser($id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $user = $this->getUserById($id, $entityManager);

            if (!$user) {
                return new JsonResponse('User not found', 404);
            }

            $this->removeUser($user, $entityManager);

            if ($this->userExists($id, $entityManager)) {
                throw new \Exception("User was not deleted");
            }

            return new JsonResponse('', 204);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get user by ID or return null if not found.
     *
     * @param int                    $id
     * @param EntityManagerInterface $entityManager
     *
     * @return User|null
     */
    private function getUserById($id, EntityManagerInterface $entityManager): ?User
    {
        return $entityManager->getRepository(User::class)->find($id);
    }

    /**
     * Remove user from the database.
     *
     * @param User                   $user
     * @param EntityManagerInterface $entityManager
     */
    private function removeUser(User $user, EntityManagerInterface $entityManager): void
    {
        $entityManager->remove($user);
        $entityManager->flush();
    }

    /**
     * Check if user still exists in the database.
     *
     * @param int                    $id
     * @param EntityManagerInterface $entityManager
     *
     * @return bool
     */
    private function userExists($id, EntityManagerInterface $entityManager): bool
    {
        return (bool) $entityManager->getRepository(User::class)->find($id);
    }
}
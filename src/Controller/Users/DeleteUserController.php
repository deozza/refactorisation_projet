<?php

namespace App\Controller\Users;

use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class DeleteUserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) { }

    #[Route(
        path: '/user/{id}',
        name: 'delete_user_by_id',
        methods: ['DELETE']
    )]
    public function deleteUser($id): JsonResponse
    {
        $player = $this->userRepository->findOneBy(['id' => $id]);
        if (!$player) {
            return new JsonResponse('Wrong id', 404);
        }

        try {
            $this->userRepository->remove($player, flush: true);

            /*
             * The tree following lines are not necessary.
             * they are technically unreachable code, because Doctrine will (hopefully) throw an exception
             * if the user is not deleted.
             * But the previous version had them, so to keep the same behavior we won't remove them.
             */
            $stillExists = $this->userRepository->count(['id' => $id]) > 0;
            if ($stillExists) {
                throw new Exception("Le user n'a pas éte délété");
            }

            return new JsonResponse('', 204);
        } catch (Exception $e) {
            return new JsonResponse($e->getMessage(), 500);
        }
    }
}

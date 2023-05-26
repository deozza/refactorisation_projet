<?php

namespace App\Controller\Users;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class GetUserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    #[Route(
        path: '/user/{id}',
        name: 'get_user_by_id',
        methods:['GET'],
    )]
    public function getUserById($id): JsonResponse
    {
        if (!ctype_digit($id)) {
            return new JsonResponse('Wrong id', 404);
        }

        $player = $this->userRepository->findOneBy(['id'=>$id]);

        if (!$player) {
            return new JsonResponse('Wrong id', 404);
        }

        return new JsonResponse([
            'name'=>$player->getName(),
            'age'=>$player->getAge(),
            'id'=>$player->getId()
        ], 200);
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use App\UseCase\UserUseCase;

class UserController extends AbstractController
{

    private UserUseCase $userUseCase;

    public function __construct(UserUseCase $userUseCase)
    {
        $this->userUseCase = $userUseCase;
    }

    #[Route('/users', name: 'get_user_list', methods:['GET'])]
    public function getUserList(): JsonResponse
    {
        $users = $this->userUseCase->getUserList();
        return $this->json(
            $users,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'post_user', methods:['POST'])]
    public function postUser(Request $request): JsonResponse
    {
        $dataAsArray = json_decode(json: $request->getContent(), associative: true);

        try{
            $createdUser = $this->userUseCase->createUser($dataAsArray);

            return $this->json(
                $createdUser,
                Response::HTTP_CREATED,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        }catch(BadRequestHttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/user/{userId}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserByIdt(int $userId): JsonResponse
    {
        try{
            $user = $this->userUseCase->getUserById($userId);
            return $this->json(
                $user,
                Response::HTTP_OK,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        }catch(NotFoundHttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/user/{userId}', name: 'patch_user_by_id', methods:['PATCH'])]
    public function patchUserById(int $userId, Request $request): JsonResponse
    {
        $dataAsArray = json_decode(json: $request->getContent(), associative: true);

        try{
            $patchedUser = $this->userUseCase->patchUserById($userId, $dataAsArray);

            return $this->json(
                $patchedUser,
                Response::HTTP_OK,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        }catch(\Exception $e){
            return $this->json(
                $e->getMessage(),
                $e->getCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        }
    }

    #[Route('/user/{userId}', name: 'delete_user_by_id', methods:['DELETE'])]
    public function deleteUserById(int $userId): JsonResponse
    {
        try{
            $this->userUseCase->deleteUserById($userId);

            return $this->json(
                null,
                Response::HTTP_NO_CONTENT,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }catch(NotFoundHttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }
}

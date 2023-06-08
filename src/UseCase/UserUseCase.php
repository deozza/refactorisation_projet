<?php

namespace App\UseCase;

use App\Service\UserService;
use App\Entity\User;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserUseCase
{

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @return User[]
     */
    public function getUserList(): array
    {
        return $this->userService->getUserList();
    }

    /**
     * @param array $input
     * @return User
     * @throw BadRequestHttpException 
     */
    public function createUser(array $input): User
    {
        $result = $this->userService->validateUserCreation($input);

        if($result instanceof FormErrorIterator){
            throw new BadRequestHttpException(json_encode($result));
        }

        $this->userService->save($result);

        return $result;
    }

    /**
     * @param int $id
     * @return User
     * @throw NotFoundHttpException 
     */
    public function getUserById(int $id): User
    {
        $result = $this->userService->getUserById($id);

        if(empty($result)){
            throw new NotFoundHttpException('Wrong id');
        }

        return $result;
    }
}

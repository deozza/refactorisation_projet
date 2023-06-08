<?php

namespace App\UseCase;

use App\Service\UserService;
use App\Entity\User;
use App\Form\CreateUserType;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

    public function createUser(array $input): User
    {
        $result = $this->userService->validateUserCreation($input);

        if($result instanceof FormErrorIterator){
            throw new BadRequestException(json_encode($result), Response::HTTP_BAD_REQUEST);
        }

        $this->userService->save($result);

        return $result;
    }
}

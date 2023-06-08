<?php

namespace App\UseCase;

use App\Service\UserService;
use App\Entity\User;

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
}

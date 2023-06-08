<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Entity\User;

class UserService
{

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @return User[]
     */
    public function getUserList(): array
    {
        return $this->userRepository->findAll();
    }
}

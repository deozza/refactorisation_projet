<?php

namespace App\Service;

use App\Entity\User;
use App\DTO\UserDTO;
use App\Form\AddUserType;
use App\Form\UpdateUserType;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;

class UserService
{
    private UserRepository $userRepository;
    private FormFactoryInterface $formFactory;

    public function __construct(FormFactoryInterface $formFactory, UserRepository $userRepository)
    {
        $this->formFactory = $formFactory;
        $this->userRepository = $userRepository;
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function getUser(int $id):  User | null
    {
        return $this->userRepository->find($id);
    }

    public function save(?User $user = null): void
    {
        $this->userRepository->save($user);
    }

    public function deleteUser(User $user): void
    {
        $this->userRepository->delete($user);
    }
}

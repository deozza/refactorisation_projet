<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactory;

class UserService
{

    private UserRepository $userRepository;
    private FormFactory $formFactory;

    public function __construct(UserRepository $userRepository, FormFactory $formFactory)
    {
        $this->userRepository = $userRepository;
        $this->formFactory = $formFactory;
    }

    /**
     * @return User[]
     */
    public function getUserList(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * @param array $input
     * @param User $user
     * 
     * @return User|FormErrorIterator
     */
    public function validateUserCreation(array $input): User | FormErrorIterator
    {
        $user = new User();

        $createUserForm = $this->formFactory->create(CreateUserType::class, $user);
        $createUserForm->submit($input);

        if($createUserForm->isValid() === false){
            return $createUserForm->getErrors();
        }

        return $user;
    }

    /**
     * @param User|null $user
     */
    public function save(?User $user)
    {
        if(empty($user) === false){
            $this->userRepository->persist($user);
        }

        $this->userRepository->flush();
    }
}

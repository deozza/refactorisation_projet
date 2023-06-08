<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;

class UserService
{

    private UserRepository $userRepository;
    private FormFactoryInterface $formFactory;

    public function __construct(UserRepository $userRepository, FormFactoryInterface $formFactory)
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
     * @param int $id
     * 
     * @return User|null
     */
    public function getUserById(int $id): User | null 
    {
        return $this->userRepository->find($id);
    }

    /**
     * @param array $input
     * 
     * @param User $user
     * 
     * @return User|FormErrorIterator
     */
    public function validateUserPatch(User $user, array $input): User | FormErrorIterator
    {
        $patchUserForm = $this->formFactory->create(PatchUserType::class, $user);
        $patchUserForm->submit($input, false);

        if($patchUserForm->isValid() === false){
            return $patchUserForm->getErrors();
        }

        return $user;
    }

    /**
     * @param User|null $user
     * 
     * @return void
     */
    public function save(?User $user = null): void
    {
        if(empty($user) === false){
            $this->userRepository->persist($user);
        }

        $this->userRepository->flush();
    }

    /**
     * @param User $user
     * 
     * @return void
     */
    public function delete(User $user): void
    {
        $this->userRepository->remove($user);
        $this->save();
    }
}

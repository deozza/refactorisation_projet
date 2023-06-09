<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Entity\User;
use App\Entity\UserInput;
use App\Form\CreateUserType;
use App\Form\PatchUserType;
use Symfony\Component\Form\FormError;
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
        $userInput = new UserInput();

        $createUserForm = $this->formFactory->create(CreateUserType::class, $userInput);
        $createUserForm->submit($input);

        if($createUserForm->isValid() === false){
            return $createUserForm->getErrors();
        }

        $userAlreadyExists = $this->userRepository->findOneBy(['name' => $userInput->getNom()]);

        if(empty($userAlreadyExists) === false){
            $createUserForm->addError(new FormError('Name already exists'));

            return $createUserForm->getErrors();
        }

        $user = new User();
        $user->setName($userInput->getNom());
        $user->setAge($userInput->getAge());

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
        $userInput = new UserInput();

        $patchUserForm = $this->formFactory->create(PatchUserType::class, $userInput);
        $patchUserForm->submit($input, false);

        if($patchUserForm->isValid() === false){
            return $patchUserForm->getErrors();
        }

        $userAlreadyExists = $this->userRepository->findOneBy(['name' => $userInput->getNom()]);

        if(empty($userAlreadyExists) === false){
            $patchUserForm->addError(new FormError('Name already exists'));

            return $patchUserForm->getErrors();
        }

        if(empty($userInput->getNom()) === false){
            $user->setName($userInput->getNom());
        }

        if(empty($userInput->getAge()) === false){
            $user->setAge($userInput->getAge());
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
        $this->userRepository->save($user);
    }

    /**
     * @param User $user
     * 
     * @return void
     */
    public function delete(User $user): void
    {
        $this->userRepository->delete($user);
    }
}

<?php

namespace App\Controller\Users;

use App\Entity\User;
use App\Forms\UserForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UpdateUserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {}

    #[Route(
        path: '/user/{id}',
        name: 'update_user',
        methods: ['PATCH']
    )]
    public function updateUser($id, Request $request): JsonResponse
    {
        $player = $this->userRepository->findOneBy(['id' => $id]);
        if (!$player) {
            return new JsonResponse('Wrong id', 404);
        }

        $form = UserForm::buildUserForm($this->createFormBuilder())->getForm();
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'nom':
                    $user = $this->userRepository->findOneBy(['name' => $data['nom']]);
                    if (!$user) {
                        return new JsonResponse('Name already exists', 400);
                    }

                    $player->setName($data['nom']);
                    $this->entityManager->flush();
                    break;
                case 'age':
                    if ($data['age'] < User::MINIMAL_AGE) {
                        return new JsonResponse('Wrong age', 400);
                    }

                    $player->setAge($data['age']);
                    $this->entityManager->flush();
                    break;
            }
        }

        return new JsonResponse([
                'name' => $player->getName(),
                'age' => $player->getAge(),
                'id' => $player->getId()
            ], 200);
    }
}

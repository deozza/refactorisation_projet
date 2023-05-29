<?php

namespace App\Controller\Users;

use App\Entity\User;
use App\Forms\UserForm;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class CreateUserController extends AbstractController
{

    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    #[Route(
        path: '/users',
        name: 'user_post',
        methods: ['POST']
    )]
    public function createUser(Request $request): JsonResponse
    {
        $form = UserForm::buildUserForm($this->createFormBuilder())->getForm();
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        if ($form->get('age')->getData() <= User::MINIMAL_AGE) {
            return new JsonResponse('Wrong age', 400);
        }

        $alreadyExists = $this->userRepository->findOneBy(['name' => $data['nom']]);
        if ($alreadyExists) {
            return new JsonResponse('Name already exists', 400);
        }

        $player = new User();
        $data = $form->getData();
        $player->setName($data['nom']);
        $player->setAge($data['age']);

        $this->userRepository->save($player, flush: true);

        return $this->json(
            $player,
            201,
            ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

}

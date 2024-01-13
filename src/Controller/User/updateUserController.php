<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class updateUserController extends AbstractController
{
    #[Route('/user/{identifiant}', name: 'update_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
    {
        $joueur = $entityManager->getRepository(User::class)->find($identifiant);

        if (!$joueur) {
            return new JsonResponse('Wrong id', 404);
        }

        if ($request->getMethod() !== 'PATCH') {
            return new JsonResponse('Wrong method', 405);
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createFormBuilder()
            ->add('nom', TextType::class, ['required' => false])
            ->add('age', NumberType::class, ['required' => false])
            ->getForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'nom':
                    $userWithSameName = $entityManager->getRepository(User::class)->findBy(['name' => $data['nom']]);
                    if (count($userWithSameName) === 0) {
                        $joueur->setName($data['nom']);
                        $entityManager->flush();
                    } else {
                        return new JsonResponse('Name already exists', 400);
                    }
                    break;
                case 'age':
                    if ($data['age'] > 21) {
                        $joueur->setAge($data['age']);
                        $entityManager->flush();
                    } else {
                        return new JsonResponse('Wrong age', 400);
                    }
                    break;
            }
        }

        return new JsonResponse([
            'name' => $joueur->getName(),
            'age' => $joueur->getAge(),
            'id' => $joueur->getId()
        ], 200);
    }
}

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
use Symfony\Component\Validator\Constraints as Assert;

class createUserController extends AbstractController
{
    #[Route('/users', name: 'user_post', methods:['POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse('Wrong method', 405);
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createFormBuilder()
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 1, 'max' => 255])
                ]
            ])
            ->add('age', NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->getForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return new JsonResponse('Invalid form', 400);
        }

        $formData = $form->getData();

        if ($formData['age'] <= 21) {
            return new JsonResponse('Wrong age', 400);
        }

        $existingUser = $entityManager->getRepository(User::class)->findBy(['name' => $formData['nom']]);
        if (count($existingUser) === 0) {
            $joueur = new User();
            $joueur->setName($formData['nom']);
            $joueur->setAge($formData['age']);
            $entityManager->persist($joueur);
            $entityManager->flush();

            return $this->json(
                $joueur,
                201,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        } else {
            return new JsonResponse('Name already exists', 400);
        }
    }
}

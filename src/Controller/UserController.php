<?php

namespace App\Controller;

use PDO;
use App\Entity\User;
use App\Form\Type\CreateUserType;
use App\Form\Type\UpdateUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
	#[Route('/users', name: 'list_users', methods: ['GET'])]
	public function listUsers(EntityManagerInterface $entityManager): JsonResponse
	{
		$data = $entityManager->getRepository(User::class)->findAll();
		return $this->json(
			$data,
			headers: ['Content-Type' => 'application/json;charset=UTF-8']
		);
	}

	#[Route('/users', name: 'user_post', methods: ['POST'])]
	public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
	{
		try {
			if ($request->getMethod() === 'POST') {
				$data = json_decode($request->getContent(), true);

				$form = $this->createForm(CreateUserType::class);
				$form->submit($data);

				if ($form->isSubmitted() && $form->isValid()) {
					$data = $form->getData();

					if ($data['age'] > 21) {
						$user = $entityManager->getRepository(User::class)->findBy(['name' => $data['nom']]);

						if (count($user) === 0) {
							$joueur = new User();
							$joueur->setName($data['nom']);
							$joueur->setAge($data['age']);
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
					} else {
						return new JsonResponse('Wrong age', 400);
					}
				} else {
					return new JsonResponse('Invalid form', 400);
				}
			} else {
				return new JsonResponse('Wrong method', 405);
			}
			return new JsonResponse("Success", 201);
		} catch (\Exception $e) {
			return new JsonResponse($e->getMessage(), 400);
		}
	}

	#[Route('/user/{id}', name: 'get_user_by_id', methods: ['GET'])]
	public function getUserById($id, EntityManagerInterface $entityManager): JsonResponse
	{
		if (ctype_digit($id)) {
			$user = $entityManager->getRepository(User::class)->find($id);
			if ($user) {
				return new JsonResponse(array('name' => $user->getName(), "age" => $user->getAge(), 'id' => $user->getId()), 200);
			} else {
				return new JsonResponse('User not found', 404);
			}
		}
		return new JsonResponse('Invalid ID', 404);
	}

	#[Route('/user/{identifiant}', name: 'update_user', methods: ['PATCH'])]
	public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
	{
		$user = $entityManager->getRepository(User::class)->find($identifiant);

		if (!$user) {
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

		if (isset($data['nom'])) {
			$existingUser = $entityManager->getRepository(User::class)->findBy(['name' => $data['nom']]);
			if ($existingUser && $existingUser[0]->getId() !== $user->getId()) {
				return new JsonResponse('Name already exists', 400);
			}

			$user->setName($data['nom']);
		}

		if (isset($data['age'])) {
			if ($data['age'] <= 21) {
				return new JsonResponse('Wrong age', 400);
			}

			$user->setAge($data['age']);
		}

		$entityManager->flush();

		return new JsonResponse([
			'name' => $user->getName(),
			'age' => $user->getAge(),
			'id' => $user->getId()
		], 200);
	}


	#[Route('/user/{id}', name: 'delete_user_by_id', methods: ['DELETE'])]
	public function deleteUserById($id, EntityManagerInterface $entityManager): JsonResponse | null
	{
		$user = $entityManager->getRepository(User::class)->find($id);
		if ($user) {
			try {
				$entityManager->remove($user);
				$entityManager->flush();

				$userStillExists = $entityManager->getRepository(User::class)->find($id);

				if ($userStillExists) {
					throw new \Exception('The user was not deleted');
					return null;
				} else {
					return new JsonResponse('', 204);
				}
			} catch (\Exception $e) {
				return new JsonResponse($e->getMessage(), 500);
			}
		} else {
			return new JsonResponse('User not found', 404);
		}
	}
}
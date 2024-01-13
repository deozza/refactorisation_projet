<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends AbstractController
{
    #[Route('/users', name: 'get_user_list', methods:['GET'])]
    public function getUsersList(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'create_user', methods:['POST'])]
    public function createUser(Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
		if($request->getMethod() !== 'POST'){
			return new JsonResponse('Wrong method', 405);
		}
		$data = json_decode($request->getContent(), true);
		$form = $this->createFormBuilder()
				->add('nom', TextType::class, [
						'constraints'=>[
								new Assert\NotBlank(),
								new Assert\Length(['min'=>1, 'max'=>255])
						]
				])
				->add('age', NumberType::class, [
						'constraints'=>[
								new Assert\NotBlank()
						]
				])
				->getForm();

		$form->submit($data);


		if(!$form->isValid()) {
			return new JsonResponse('Invalid form', 400);
		}
		if($data['age'] < 21){
			return new JsonResponse('Wrong age', 400);
		}
		$dbUser = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
		if(count($dbUser) !== 0){
			return new JsonResponse('Name already exists', 400);
		}
		$newUser = new User();
		$newUser->setName($data['nom']);
		$newUser->setAge($data['age']);
		$entityManager->persist($newUser);
		$entityManager->flush();

		return $this->json(
				$newUser,
				201,
				['Content-Type' => 'application/json;charset=UTF-8']
		);
    }

    #[Route('/user/{id}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserById($id, EntityManagerInterface $entityManager): JsonResponse
    {
		if(!ctype_digit($id)){
			return new JsonResponse('Wrong id', 404);
		}
		$dbUser = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

		if(count($dbUser) !== 1){
			return new JsonResponse('Wrong id', 404);
		}
		return new JsonResponse(array('name'=>$dbUser[0]->getName(), "age"=>$dbUser[0]->getAge(), 'id'=>$dbUser[0]->getId()), 200);
	}

    #[Route('/user/{id}', name: 'update_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $id, Request $request): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

        if(count($user) !== 1) {
			return new JsonResponse('Wrong id', 404);
		}
		if($request->getMethod() !== 'PATCH'){
			return new JsonResponse('Wrong method', 405);
		}
		$data = json_decode($request->getContent(), true);
		$form = $this->createFormBuilder()
				->add('nom', TextType::class, array(
						'required'=>false
				))
				->add('age', NumberType::class, [
						'required' => false
				])
				->getForm();

		$form->submit($data);
		if(!$form->isValid()) {
			return new JsonResponse('Invalid form', 400);
		}
		foreach($data as $key=>$value){
			switch($key){
				case 'nom':
					$dbUser = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
					if(count($dbUser) !== 0){
						return new JsonResponse('Name already exists', 400);
					}
					$user[0]->setName($data['nom']);
					$entityManager->flush();
					break;
				case 'age':
					if($data['age'] < 21){
						return new JsonResponse('Wrong age', 400);
					}
					$user[0]->setAge($data['age']);
					$entityManager->flush();
					break;
			}
		}

		return new JsonResponse(array('name'=>$user[0]->getName(), "age"=>$user[0]->getAge(), 'id'=>$user[0]->getId()), 200);
	}


    #[Route('/user/{id}', name: 'delete_user_by_id', methods:['DELETE'])]
    public function deleteUserById($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $user = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
        if(count($user) !== 1){
			return new JsonResponse('Wrong id', 404);
		}
		try{
			$entityManager->remove($user[0]);
			$entityManager->flush();

			$userNotDeleted = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);

			if(!empty($userNotDeleted)){
				throw new \Exception("User not deleted");
			}
			return new JsonResponse('', 204);
		}catch(\Exception $e){
			return new JsonResponse($e->getMessage(), 500);
		}
    }
}

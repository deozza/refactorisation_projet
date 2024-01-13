<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Service\UserService;
use PDO;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('', name: 'user_index', methods:['GET'])]
    public function index(UserService $userService): JsonResponse
    {
        $users = $userService->getAllUsers();
        return $this->json(
            $users,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );

    }

    #[Route('/{id}', name: 'user_show', methods:['GET'])]
    public function show($id, UserService $userService): JsonResponse
    {
        if(!ctype_digit($id)){
            return new JsonResponse('Wrong id', Response::HTTP_NOT_FOUND);
        }

        $user = $userService->getUser($id);
        
        if(!$user){
            return new JsonResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        return $this->json(
            $user,
            Response::HTTP_OK,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/{id}', name: 'user_delete', methods:['DELETE'])]
    public function deleteUser($id, UserService $userService): JsonResponse | null
    {
        if(!ctype_digit($id)){
            return new JsonResponse('Wrong id', Response::HTTP_NOT_FOUND);
        }
        
        $user = $userService->getUser($id);

        if(!$user){
            return new JsonResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        $userService->deleteUser($user);

        $stillExist = $userService->getUser($id);
        if(!empty($stillExist)){
            return new \Exception("User not deleted", 500);
        }
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'user_create', methods:['POST'])]
    public function create(Request $request,EntityManagerInterface $entityManager, UserService $userService): JsonResponse
    {
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

        if(!($form->isValid())) {
            return new JsonResponse('Invalid form', Response::HTTP_BAD_REQUEST);
        }

        if($data['age'] <= 21){
            return new JsonResponse('Wrong age', Response::HTTP_BAD_REQUEST);
        }

            $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
            if(count($user) !== 0){
                return new JsonResponse('Name already exists', Response::HTTP_BAD_REQUEST);
            }

            $user = new User();
            $user->setName($data['nom']);
            $user->setAge($data['age']);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json(
                        $user,
                        Response::HTTP_CREATED,
                        ['Content-Type' => 'application/json;charset=UTF-8']
                    ); 
    }

}

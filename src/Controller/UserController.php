<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\UseCase\UserUseCase;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends AbstractController
{

    private UserUseCase $userUseCase;

    public function __construct(UserUseCase $userUseCase)
    {
        $this->userUseCase = $userUseCase;
    }

    #[Route('/users', name: 'get_user_list', methods:['GET'])]
    public function getUserList(): JsonResponse
    {
        $users = $this->userUseCase->getUserList();
        return $this->json(
            $users,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    #[Route('/users', name: 'post_user', methods:['POST'])]
    public function postUser(Request $request): JsonResponse
    {
        $dataAsArray = json_decode(json: $request->getContent(), associative: true);

        try{
            $createdUser = $this->userUseCase->createUser($dataAsArray);

            return $this->json(
                $createdUser,
                Response::HTTP_CREATED,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        }catch(BadRequestHttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/user/{userId}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserByIdt(int $userId): JsonResponse
    {
        try{
            $user = $this->userUseCase->getUserById($userId);
            return $this->json(
                $user,
                Response::HTTP_OK,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        }catch(NotFoundHttpException $e){
            return $this->json(
                $e->getMessage(),
                $e->getCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/user/{identifiant}', name: 'udpate_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
    {
        $joueur = $entityManager->getRepository(User::class)->findBy(['id'=>$identifiant]);


        if(count($joueur) == 1){

            // ETO 01/05/2023 on n'utilise plus le put
            // on est passé au patch
            // parce que c'etait plus simple a utiliser
            /*
            if($request->getMethod() == 'PUT'){
                $data = json_decode($request->getContent(), true);

                $form = $this->createFormBuilder()
                    ->add('nom', TextType::class)
                    ->add('age', NumberType::class)
                    ->getForm();

                $form->submit($data);

                if($form->isValid()) {
                    if($data['age'] > 21){
                        $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                        if(count($user) === 0){
                            $joueur->setName($data['nom']);
                            $joueur->setAge($data['age']);
                            $entityManager->persist($joueur);
                            $entityManager->flush();

                            return new JsonResponse($joueur, 200);
                        }else{
                            return new JsonResponse('Name already exists', 400);
                        }
                    }else{
                        return new JsonResponse('Wrong age', 400);
                    }
                }else{
                    return new JsonResponse('Invalid form', 400);
                }
            }elseif($request->getMethod() == 'PATCH'){
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
                if($form->isValid()) {
                    if($data['age'] > 21){
                        $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                        if(count($user) === 0){
                            $joueur->setName($data['nom']);
                            $joueur->setAge($data['age']);
                            $entityManager->flush();

                            return new JsonResponse($joueur, 200);
                        }else{
                            return new JsonResponse('Name already exists', 400);
                        }
                    }else{
                        return new JsonResponse('Wrong age', 400);
                    }
                }else{
                    return new JsonResponse('Invalid form', 400);
                }
            }else{
                $data = json_decode($request->getContent(), true);
                return new JsonResponse('Wrong method', 405);
            }
            */

            if($request->getMethod() == 'PATCH'){
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
                if($form->isValid()) {

                    foreach($data as $key=>$value){
                        switch($key){
                            case 'nom':
                                $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                                if(count($user) === 0){
                                    $joueur[0]->setName($data['nom']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Name already exists', 400);
                                }
                                break;
                            case 'age':
                                if($data['age'] > 21){
                                    $joueur[0]->setAge($data['age']);
                                    $entityManager->flush();
                                }else{
                                    return new JsonResponse('Wrong age', 400);
                                }
                                break;
                        }
                    }
                }else{
                    return new JsonResponse('Invalid form', 400);
                }
            }else{
                $data = json_decode($request->getContent(), true);
                return new JsonResponse('Wrong method', 405);
            }

            return new JsonResponse(array('name'=>$joueur[0]->getName(), "age"=>$joueur[0]->getAge(), 'id'=>$joueur[0]->getId()), 200);
        }else{
            return new JsonResponse('Wrong id', 404);
        }    
    }

    #[Route('/user/{id}', name: 'delete_user_by_identifiant', methods:['DELETE'])]
    public function suprUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $joueur = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
        if(count($joueur) == 1){
            try{
                $entityManager->remove($joueur[0]);
                $entityManager->flush();

                $existeEncore = $entityManager->getRepository(User::class)->findBy(['id'=>$id]);
    
                if(!empty($existeEncore)){
                    throw new \Exception("Le user n'a pas éte délété");
                    return null;
                }else{
                    return new JsonResponse('', 204);
                }
            }catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 500);
            }
        }else{
            return new JsonResponse('Wrong id', 404);
        }    
    }
}

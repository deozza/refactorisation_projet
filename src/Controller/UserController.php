<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use PDO;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends AbstractController
{
    #[Route('/users', name: 'liste_des_users', methods:['GET'])]
    public function getUsersList(EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $entityManager->getRepository(User::class)->findAll();
        return $this->json(
            $data,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );

    }

    #[Route('/users', name: 'user_post', methods:['POST'])]
    public function createUser(Request $request,EntityManagerInterface $entityManager): JsonResponse
    {
        $datUserAsArray = json_decode(json: $request->getContent(), associative: true);
        if($dataUserAsArray === null){
            $dataUserAsArray = [];
        }
        try{
            $createdUser = $this->userUseCase->createUser($dataUserAsArray);

            return $this->json(
                $createdUser,
                Response::HTTP_CREATED,
                ['Content-Type' => 'application/json;charset=UTF-8']
            );

        }catch(BadRequestHttpException $error){
            return $this->json(
                $error->getMessage(),
                $error->getStatusCode(),
                ['Content-Type' => 'application/json;charset=UTF-8']
            );
        }
    }

    #[Route('/user/{identifiant}', name: 'get_user_by_id', methods:['GET'])]
    public function getUserWithIdentifiant($identifiant, EntityManagerInterface $entityManager): JsonResponse
    {
        if(ctype_digit($identifiant)){
            $joueur = $entityManager->getRepository(User::class)->findBy(['id'=>$identifiant]);
            if(count($joueur) == 1){
                return new JsonResponse(array('name'=>$joueur[0]->getName(), "age"=>$joueur[0]->getAge(), 'id'=>$joueur[0]->getId()), 200);
            }else{
                return new JsonResponse('Wrong id', 404);
            }
        }
        return new JsonResponse('Wrong id', 404);
    }

    #[Route('/user/{identifiant}', name: 'udpate_user', methods:['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
{
    $joueur = $entityManager->getRepository(User::class)->findBy(['id' => $identifiant]);

    if (count($joueur) !== 1) {
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
                $user = $entityManager->getRepository(User::class)->findBy(['name' => $data['nom']]);
                if (count($user) === 0) {
                    $joueur[0]->setName($data['nom']);
                    $entityManager->flush();
                } else {
                    return new JsonResponse('Name already exists', 400);
                }
                break;
            case 'age':
                if ($data['age'] > 21) {
                    $joueur[0]->setAge($data['age']);
                    $entityManager->flush();
                } else {
                    return new JsonResponse('Wrong age', 400);
                }
                break;
        }
    }

    return new JsonResponse(['name' => $joueur[0]->getName(), 'age' => $joueur[0]->getAge(), 'id' => $joueur[0]->getId()], 200);
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
    private function isUsernameExists(string $username, EntityManagerInterface $entityManager): bool
    {
        $user = $entityManager->getRepository(User::class)->findBy(['name' => $username]);
        return count($user) > 0;
    }
    
}

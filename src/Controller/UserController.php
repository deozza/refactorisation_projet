<?php

namespace App\Controller;
use App\Form\UserFormType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

class UserController extends AbstractController
{
    #[Route('/users', name: 'liste_des_users', methods: ['GET'])]
    public function ‚(EntityManagerInterface $entityManager): JsonResponse
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
        if ($request->getMethod() === 'POST') {
            $data = json_decode($request->getContent(), true);
    
            $form = $this->createForm(UserFormType::class);
            $form->submit($data);
            if($form->isValid())
            {
                if($data['age'] < 21){
                    return new JsonResponse('Wrong age', 400);
                }else{
                     $user = $entityManager->getRepository(User::class)->findBy(['name'=>$data['nom']]);
                    if(count($user) === 0){
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
                    }else{
                        return new JsonResponse('Name already exists', 400);
                    }
                }                
                return new JsonResponse('Invalid form', 400);
            }
        } else {
            return new JsonResponse('Wrong method', 405);
        }
    }
    


    #[Route('/user/{identifiant}', name: 'get_user_by_id', methods: ['GET'])]
    public function getUserWithIdentifiant($identifiant, EntityManagerInterface $entityManager): JsonResponse
    {
        if (ctype_digit($identifiant)) {
            $joueur = $entityManager->getRepository(User::class)->findBy(['id' => $identifiant]);
            if (count($joueur) == 1) {
                return new JsonResponse(array('name' => $joueur[0]->getName(), "age" => $joueur[0]->getAge(), 'id' => $joueur[0]->getId()), 200);
            } else {
                return new JsonResponse('Wrong id, this user does not exist', 404);
            }
        }
        return new JsonResponse('Wrong id', 404);
    }

    #[Route('/user/{identifiant}', name: 'udpate_user', methods: ['PATCH'])]
    public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
    {
        $joueur = $entityManager->getRepository(User::class)->findBy(['id' => $identifiant]);

        if (count($joueur) == 1) {
            if ($request->getMethod() == 'PATCH') {
                $data = json_decode($request->getContent(), true);
                $form = $this->createForm(UserFormType::class);
                $form->submit($data);
                if ($form->isValid()) {

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
                } else {
                    return new JsonResponse('Invalid form', 400);
                }
            } else {
                $data = json_decode($request->getContent(), true);
                return new JsonResponse('Wrong method', 405);
            }

            return new JsonResponse(array('name' => $joueur[0]->getName(), "age" => $joueur[0]->getAge(), 'id' => $joueur[0]->getId()), 200);
        } else {
            return new JsonResponse('Wrong id', 404);
        }
    }

    #[Route('/user/{id}', name: 'delete_user_by_identifiant', methods: ['DELETE'])]
    public function suprUser($id, EntityManagerInterface $entityManager): JsonResponse | null
    {
        $joueur = $entityManager->getRepository(User::class)->findBy(['id' => $id]);
        if (count($joueur) == 1) {
            try {
                $entityManager->remove($joueur[0]);
                $entityManager->flush();
                $existeEncore = $entityManager->getRepository(User::class)->findBy(['id' => $id]);
                if (!empty($existeEncore)) {
                    throw new \Exception("Le user n'a pas éte délété");
                    return null;
                } else {
                    return new JsonResponse('Utilisateur a été bien supprimé', 204);
                }
            } catch (\Exception $e) {
                return new JsonResponse($e->getMessage(), 500);
            }
        } else {
            return new JsonResponse('Wrong id', 404);
        }
    }
}

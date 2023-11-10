<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\UserFormType;
use Symfony\Component\HttpFoundation\Request;

class UpdateUserController extends AbstractController
{
    #[Route('/user/{identifiant}', name: 'udpate_user', methods:['PATCH'])]
    /**
     * Update a user's information based on a PATCH request.
     *
     * @param EntityManagerInterface $entityManager The entity manager for database interaction.
     * @param int|string $identifiant The identifier of the user to be updated.
     * @param Request $request The incoming HTTP request.
     *
     * @return JsonResponse A JSON response containing the updated user details or an error message.
     */
    public function updateUser(EntityManagerInterface $entityManager, $identifiant, Request $request): JsonResponse
    {
        $joueur = $entityManager->getRepository(User::class)->findBy(['id'=>$identifiant]);


        if(count($joueur) == 1){
            if($request->getMethod() == 'PATCH'){
                $data = json_decode($request->getContent(), true);
                $form = $this->createForm(UserFormType::class);

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
}

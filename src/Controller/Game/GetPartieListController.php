<?php

namespace App\Controller\Game;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GetPartieListController extends AbstractController
{
    #[Route('/games', name: 'get_list_of_games', methods:['GET'])]
    /**
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function getPartieList(EntityManagerInterface $entityManager): JsonResponse
    {
        $games = $entityManager->getRepository(Game::class)->findAll();

        return $this->json($games);
    }
}
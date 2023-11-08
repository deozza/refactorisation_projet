<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class GameService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    public function launchGame($currentUserId)
    {
        if ($currentUserId === null || !ctype_digit($currentUserId)) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $this->userRepository->find($currentUserId);

        if ($currentUser === null) {
            return new JsonResponse('User not found', 401);
        }

        return $this->createNewGame($currentUser);
    }

    public function createNewGame($currentUser)
    {
        if ($currentUser === null) {
            return ['status' => 'error', 'message' => 'User not found'];
        }
        $newGame = new Game();
        $newGame->setState('pending');
        $newGame->setPlayerLeft($currentUser);

        $this->entityManager->persist($newGame);
        $this->entityManager->flush();

        return ['status' => 'success', 'data' => $newGame];
    }

    public function defineWinner(string $leftChoice, string $rightChoice): string
    {
        if ($leftChoice === $rightChoice) {
            return 'draw';
        }

        if (($leftChoice === 'rock' && $rightChoice === 'scissors') ||
            ($leftChoice === 'paper' && $rightChoice === 'rock') ||
            ($leftChoice === 'scissors' && $rightChoice === 'paper')
        ) {
            return 'winLeft';
        } else {
            return 'winRight';
        }
    }
}

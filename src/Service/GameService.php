<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class GameService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createNewGame(User $currentUser): Game
    {
        $newGame = new Game();
        $newGame->setState('pending');
        $newGame->setPlayerLeft($currentUser);

        $this->entityManager->persist($newGame);
        $this->entityManager->flush();

        return $newGame;
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

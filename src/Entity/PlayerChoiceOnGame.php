<?php

namespace App\Entity;


class PlayerChoiceOnGame
{
    const CHOICE_ROCK = 'rock';
    const CHOICE_PAPER = 'paper';
    const CHOICE_SCISSORS = 'scissors';

    const CHOICES = [
        self::CHOICE_ROCK,
        self::CHOICE_PAPER,
        self::CHOICE_SCISSORS
    ];


    private string $choice;

    public function getChoice(): ?string
    {
        return $this->choice;
    }

    public function setChoice(string $choice): self
    {
        $this->choice = $choice;

        return $this;
    }
}

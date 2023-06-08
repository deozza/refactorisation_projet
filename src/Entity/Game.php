<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    const STATE_PENDING = 'pending';
    const STATE_ONGOING = 'ongoing';
    const STATE_FINISHED = 'finished';

    const RESULT_DRAW = 'draw';
    const RESULT_WIN_LEFT = 'winLeft';
    const RESULT_WIN_RIGHT = 'winRight';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $state = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $playerLeft = null;

    #[ORM\ManyToOne]
    private ?User $playerRight = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $playLeft = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $playRight = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $result = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getPlayerLeft(): ?User
    {
        return $this->playerLeft;
    }

    public function setPlayerLeft(?User $playerLeft): self
    {
        $this->playerLeft = $playerLeft;

        return $this;
    }

    public function getPlayerRight(): ?User
    {
        return $this->playerRight;
    }

    public function setPlayerRight(?User $playerRight): self
    {
        $this->playerRight = $playerRight;

        return $this;
    }

    public function getPlayLeft(): ?string
    {
        return $this->playLeft;
    }

    public function setPlayLeft(?string $playLeft): self
    {
        $this->playLeft = $playLeft;

        return $this;
    }

    public function getPlayRight(): ?string
    {
        return $this->playRight;
    }

    public function setPlayRight(?string $playRight): self
    {
        $this->playRight = $playRight;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): self
    {
        $this->result = $result;

        return $this;
    }
}

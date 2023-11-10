<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
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

    /**
     * Get the id of the game.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the state of the game.
     *
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Set the state of the game.
     *
     * @param string $state
     * @return self
     */
    public function setState(string $state): self
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Get the left player of the game.
     *
     * @return User|null
     */
    public function getPlayerLeft(): ?User
    {
        return $this->playerLeft;
    }

    /**
     * Set the left player of the game.
     *
     * @param User|null $playerLeft
     * @return self
     */
    public function setPlayerLeft(?User $playerLeft): self
    {
        $this->playerLeft = $playerLeft;
        return $this;
    }

    /**
     * Get the right player of the game.
     *
     * @return User|null
     */
    public function getPlayerRight(): ?User
    {
        return $this->playerRight;
    }

    /**
     * Set the right player of the game.
     *
     * @param User|null $playerRight
     * @return self
     */
    public function setPlayerRight(?User $playerRight): self
    {
        $this->playerRight = $playerRight;
        return $this;
    }

    /**
     * Get the play made by the left player.
     *
     * @return string|null
     */
    public function getPlayLeft(): ?string
    {
        return $this->playLeft;
    }

    /**
     * Set the play made by the left player.
     *
     * @param string|null $playLeft
     * @return self
     */
    public function setPlayLeft(?string $playLeft): self
    {
        $this->playLeft = $playLeft;
        return $this;
    }

    /**
     * Get the play made by the right player.
     *
     * @return string|null
     */
    public function getPlayRight(): ?string
    {
        return $this->playRight;
    }

    /**
     * Set the play made by the right player.
     *
     * @param string|null $playRight
     * @return self
     */
    public function setPlayRight(?string $playRight): self
    {
        $this->playRight = $playRight;
        return $this;
    }

    /**
     * Get the result of the game.
     *
     * @return string|null
     */
    public function getResult(): ?string
    {
        return $this->result;
    }

    /**
     * Set the result of the game.
     *
     * @param string|null $result
     * @return self
     */
    public function setResult(?string $result): self
    {
        $this->result = $result;
        return $this;
    }
}
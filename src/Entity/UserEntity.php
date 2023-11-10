<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column()]
    private ?int $age = null;

    /**
     * User constructor.
     *
     * @param string|null $name
     * @param int|null    $age
     */
    public function __construct(?string $name = null, ?int $age = null)
    {
        $this->name = $name;
        $this->age = $age;
    }

    /**
     * Get User ID.
     * 
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get User Name.
     * 
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set User Name.
     * 
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get User Age.
     * 
     * @return int|null
     */
    public function getAge(): ?int
    {
        return $this->age;
    }

    /**
     * Set User Age.
     * 
     * @param int $age
     *
     * @return $this
     */
    public function setAge(int $age): self
    {
        $this->age = $age;
        return $this;
    }
}
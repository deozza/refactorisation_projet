<?php

namespace App\Entity;

class PersonData
{
    private ?string $fullName = null;

    private ?int $yearsOld = null;

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getYearsOld(): ?int
    {
        return $this->yearsOld;
    }

    public function setYearsOld(int $yearsOld): self
    {
        $this->yearsOld = $yearsOld;

        return $this;
    }
}

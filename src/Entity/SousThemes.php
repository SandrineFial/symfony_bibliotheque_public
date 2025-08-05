<?php

namespace App\Entity;

use App\Repository\SousThemesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: SousThemesRepository::class)]
#[Broadcast]
class SousThemes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $theme_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getThemeId(): ?int
    {
        return $this->theme_id;
    }

    public function setThemeId(int $theme_id): static
    {
        $this->theme_id = $theme_id;

        return $this;
    }
}

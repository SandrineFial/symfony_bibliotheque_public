<?php

namespace App\Entity;

use App\Repository\SousThemesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SousThemesRepository::class)]
class SousThemes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $name;

    // Relation ManyToOne avec Themes
    #[ORM\ManyToOne(targetEntity: Themes::class, inversedBy: 'sousThemes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Themes $theme = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTheme(): ?Themes
    {
        return $this->theme;
    }

    public function setTheme(?Themes $theme): static
    {
        $this->theme = $theme;
        return $this;
    }
}
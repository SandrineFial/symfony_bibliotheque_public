<?php

namespace App\Entity;

use App\Repository\ThemesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ThemesRepository::class)]
class Themes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $color = 'light';

    #[ORM\Column(length: 255, nullable: false)]
    private string $name;
    
    // Relation ManyToOne avec User
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'themes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;
    
    /**
     * @var Collection<int, SousThemes>
     */
    #[ORM\OneToMany(mappedBy: 'theme', targetEntity: SousThemes::class)]
    private Collection $sousThemes;

   public function __construct()
    {
        $this->sousThemes = new ArrayCollection();
        $this->name = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
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
    

    /**
     * @return Collection<int, SousThemes>
     */
    public function getSousThemes(): Collection
    {
        return $this->sousThemes;
    }

    public function addSousTheme(SousThemes $sousTheme): static
    {
        if (!$this->sousThemes->contains($sousTheme)) {
            $this->sousThemes->add($sousTheme);
            $sousTheme->setTheme($this);
        }

        return $this;
    }
    
    /**
     * @param Collection<int, SousThemes> $sousThemes
     */
    public function setSousThemes(Collection $sousThemes): static
    {
        $this->sousThemes = $sousThemes;

        return $this;
    }

    public function removeSousTheme(SousThemes $sousTheme): static
    {
        if ($this->sousThemes->removeElement($sousTheme)) {
            if ($sousTheme->getTheme() === $this) {
                $sousTheme->setTheme(null);
            }
        }

        return $this;
    }
}
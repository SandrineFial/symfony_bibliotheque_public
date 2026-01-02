<?php

namespace App\Entity;

use App\Repository\BooksRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BooksRepository::class)]
class Books
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $auteur = null;
   
    #[ORM\Column(length: 255, nullable: false)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $isbn = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resume = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $edition = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $editionDetail = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $annees = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $etat = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $nbPages = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aQui = null;

    // Getters and setters

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getEdition(): ?string
    {
        return $this->edition;
    }

    public function setEdition(?string $edition): static
    {
        $this->edition = $edition;
        return $this;
    }

    public function getEditionDetail(): ?string
    {
        return $this->editionDetail;
    }

    public function setEditionDetail(?string $editionDetail): static
    {
        $this->editionDetail = $editionDetail;
        return $this;
    }

    public function getAnnees(): ?string
    {
        return $this->annees;
    }

    public function setAnnees(?string $annees): static
    {
        $this->annees = $annees;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): static
    {
        $this->etat = $etat;
        return $this;
    }

    public function getNbPages(): ?int
    {
        return $this->nbPages;
    }

    public function setNbPages(?int $nbPages): static
    {
        $this->nbPages = $nbPages;
        return $this;
    }

    public function getAQui(): ?string
    {
        return $this->aQui;
    }

    public function setAQui(?string $aQui): static
    {
        $this->aQui = $aQui;
        return $this;
    }
    // Relation ManyToOne avec User
    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Themes::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Themes $theme = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\SousThemes::class)]
    #[ORM\JoinColumn(nullable: true)] // Le sous-thème est optionnel
    private ?SousThemes $sousTheme = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(string $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
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

    public function getTheme(): ?Themes
    {
        return $this->theme;
    }

    public function setTheme(?Themes $theme): static
    {
        $this->theme = $theme;
        return $this;
    }
    public function getSousTheme(): ?SousThemes
    {
        return $this->sousTheme;
    }   
    public function setSousTheme(?SousThemes $sousTheme): static
    {
        $this->sousTheme = $sousTheme;
        return $this;
    }
}
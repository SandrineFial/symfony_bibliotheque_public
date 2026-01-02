<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TextCleanerExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('clean_text', [$this, 'cleanText']),
        ];
    }

    public function cleanText(?string $text): string
    {
        if ($text === null) {
            return '';
        }
        
        // Décode les entités HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Supprime les balises HTML si présentes
        $text = strip_tags($text);
        
        // Échappe à nouveau seulement les caractères nécessaires pour l'affichage
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
        
        return $text;
    }
}

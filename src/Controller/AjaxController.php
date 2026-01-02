<?php

namespace App\Controller;

use App\Repository\SousThemesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AjaxController extends AbstractController
{
    #[Route('/get-sous-themes', name: 'get_sous_themes', methods: ['GET'])]
    public function getSousThemes(Request $request, SousThemesRepository $sousThemesRepository): JsonResponse
    {
        $themeId = $request->query->get('themeId');
        
        if (!$themeId) {
            return new JsonResponse([]);
        }
        
        $sousThemes = $sousThemesRepository->findBy(['theme' => $themeId]);
        
        $data = [];
        foreach ($sousThemes as $sousTheme) {
            $data[] = [
                'id' => $sousTheme->getId(),
                'name' => $this->cleanString($sousTheme->getName())
            ];
        }
        
        return new JsonResponse($data);
    }
    
    private function cleanString(?string $text): string
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

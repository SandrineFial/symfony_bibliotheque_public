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
                'name' => $sousTheme->getName()
            ];
        }
        
        return new JsonResponse($data);
    }
}
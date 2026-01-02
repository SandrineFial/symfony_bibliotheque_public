<?php

namespace App\Controller;

use App\Entity\Books;
use App\Entity\Themes;
use App\Entity\SousThemes;
use App\Entity\User;
use App\Repository\ThemesRepository;
use App\Repository\SousThemesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ThemesController extends AbstractController
{
    #[Route('/admin/', name: 'admin')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_themes');
    }
    #[Route('/admin/themes', name: 'admin_themes')]
    public function listeThemes(ThemesRepository $themesRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        $themes = $themesRepository->findBy(['user' => $user]);
        $collator = new \Collator('fr_FR');
        usort($themes, function($a, $b) use ($collator) {
            return $collator->compare($a->getName(), $b->getName());
        });
        $themesWithSousThemes = [];
        foreach ($themes as $theme) {
            // Récupérer les sous-catégories associés à chaque catégorie
            $sousThemes = $em->getRepository(SousThemes::class)
                ->findBy(['theme' => $theme], ['name' => 'ASC']);    
            $themesWithSousThemes[] = [
                'theme' => $theme,
                'sousThemes' => $sousThemes
            ];
        }
        return $this->render('themes/index.html.twig', [
            'themes' => $themesWithSousThemes,
        ]);
    }

    #[Route('/admin/themes/add-theme', name: 'admin_add_theme', methods: ['POST'])]
    public function addTheme(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_home');
        }
        $name = $request->request->get('theme_name');
        
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter une catégorie.');
            return $this->redirectToRoute('admin_themes');
        }
        
        if ($name) {
            $theme = new Themes();
            $theme->setName($name);
            if ($user instanceof User) {
                $theme->setUser($user); // Associer la Catégorie à l'utilisateur connecté
            }
            $existingTheme = $em->getRepository(Themes::class)->findOneBy([
                'name' => $name,
                'user' => $user
            ]);
            if ($existingTheme) {
                $this->addFlash('error', 'Une catégorie avec ce nom existe déjà.');
            }
            else {
                $em->persist($theme);
                $em->flush();
                $this->addFlash('success', 'Catégorie ajoutée avec succès !');
            }
        }
        
        return $this->redirectToRoute('admin_themes');
    }

    #[Route('/admin/themes/add-sous-theme', name: 'admin_add_sous_theme', methods: ['POST'])]
    public function addSousTheme(Request $request, EntityManagerInterface $em, ThemesRepository $themesRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_home');
        }
        $name = $request->request->get('sous_theme_name');

        $themeId = $request->request->get('theme_id');
        
        if ($name && $themeId) {
            $theme = $themesRepository->find($themeId);
            
            if ($theme) {
                $sousTheme = new SousThemes();
                $sousTheme->setName($name);
                $sousTheme->setTheme($theme);
                $existingSousTheme = $em->getRepository(SousThemes::class)->findOneBy([
                    'name' => $name,
                    'theme' => $theme
                ]);
                if ($existingSousTheme) {
                    $this->addFlash('error', 'Une sous-catégorie avec ce nom existe déjà pour cette catégorie.');
                    return $this->redirectToRoute('admin_themes');
                }
                else {
                    $em->persist($sousTheme);
                    $em->flush();
                    $this->addFlash('success', 'Sous-catégorie ajoutée avec succès !');
                }
            }
        }
        
        return $this->redirectToRoute('admin_themes');
    }
// modifier un theme
#[Route('/admin/themes/edit-theme/{id}', name: 'admin_edit_theme', methods: ['GET', 'POST'])]
public function editTheme(Request $request, EntityManagerInterface $em, ThemesRepository $themesRepository, int $id): Response
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_home');
    }
    $theme = $themesRepository->find($id);
    if (!$theme) {
        $this->addFlash('error', 'Catégorie introuvable.');
        return $this->redirectToRoute('admin_themes');
    }
// Vérifier que l'utilisateur est propriétaire du Catégorie
    if ($theme->getUser() !== $user) {
        $this->addFlash('error', 'Vous n\'avez pas le droit de modifier ce Catégorie.');
        return $this->redirectToRoute('admin_themes');
    }
    if ($request->isMethod('POST')) {
        $name = $request->request->get('theme_name');
        // Vérifier que le nom n'est pas vide après trim
        if ($name && trim($name) !== '') {
            $trimmedName = trim($name);

            // Vérifier qu'une Catégorie avec ce nom n'existe pas déjà pour cet utilisateur
            $existingTheme = $themesRepository->findOneBy([
                'name' => $trimmedName, 
                'user' => $user
            ]);
            
            if ($existingTheme && $existingTheme->getId() !== $theme->getId()) {
                $this->addFlash('error', 'Une Catégorie avec ce nom existe déjà.');
                return $this->redirectToRoute('admin_themes');
            }
            
            try {
                $theme->setName($trimmedName);
                $em->persist($theme);
                $em->flush();
                $this->addFlash('success', 'Catégorie modifiée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification de la Catégorie.');
            }
        } else {
            $this->addFlash('error', 'Le nom de la Catégorie ne peut pas être vide.');
        }

        
        return $this->redirectToRoute('admin_themes');
    }

    return $this->render('themes/edit.html.twig', [
        'theme' => $theme,
    ]);
}
// modifie un sous-theme

   #[Route('/admin/themes/edit-sous-theme/{id}', name: 'admin_edit_sous_theme', methods: ['GET', 'POST'])]
public function editSousTheme(Request $request, EntityManagerInterface $em, SousThemesRepository $sousThemesRepository, int $id): Response
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_home');
    }
    
    $sousTheme = $sousThemesRepository->find($id);
    if (!$sousTheme) {
        $this->addFlash('error', 'Sous-catégorie introuvable.');
        return $this->redirectToRoute('admin_themes');
    }

    // Vérifier que l'utilisateur est propriétaire de la Catégorie parente
    if ($sousTheme->getTheme()->getUser() !== $user) {
        $this->addFlash('error', 'Vous n\'avez pas le droit de modifier cette sous-catégorie.');
        return $this->redirectToRoute('admin_themes');
    }
    
    if ($request->isMethod('POST')) {
        $name = $request->request->get('sous_theme_name');
        
        // Vérifier que le nom n'est pas vide après trim
        if ($name && trim($name) !== '') {
            $trimmedName = trim($name);

            // Vérifier qu'un sous-Catégorie avec ce nom n'existe pas déjà pour cette Catégorie
            $existingSousTheme = $sousThemesRepository->findOneBy([
                'name' => $trimmedName, 
                'theme' => $sousTheme->getTheme()
            ]);
            
            if ($existingSousTheme && $existingSousTheme->getId() !== $sousTheme->getId()) {
                $this->addFlash('error', 'Une sous-Catégorie avec ce nom existe déjà pour cette Catégorie.');
                return $this->redirectToRoute('admin_themes');
            }
            
            try {
                $sousTheme->setName($trimmedName);
                $em->persist($sousTheme);
                $em->flush();
                $this->addFlash('success', 'Sous-catégorie modifiée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification de la sous-catégorie.');
            }
        } else {
            $this->addFlash('error', 'Le nom de la sous-catégorie ne peut pas être vide.');
        }
        
        return $this->redirectToRoute('admin_themes');
    }
    
    return $this->render('themes/edit_sous_theme.html.twig', [
        'sousTheme' => $sousTheme,
    ]);
}

    #[Route('/admin/themes/import-csv', name: 'admin_import_themes_csv', methods: ['POST'])]
public function importThemesFromCsv(Request $request, EntityManagerInterface $em, ThemesRepository $themesRepository): Response
{ // ok fonctionne
    $csvFile = $request->files->get('csv_file');
    // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_home');
        }
    
    if (!$csvFile) {
        $this->addFlash('error', 'Aucun fichier sélectionné.');
        return $this->redirectToRoute('admin_themes');
    }
    
    if ($csvFile->getClientOriginalExtension() !== 'csv') {
        $this->addFlash('error', 'Le fichier doit être au format CSV.');
        return $this->redirectToRoute('admin_themes');
    }
    
    try {
        $csvPath = $csvFile->getPathname();
        $handle = fopen($csvPath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Impossible de lire le fichier CSV.');
        }
        
        $importedThemes = 0;
        $importedSousThemes = 0;
        $skippedThemes = 0;
        $skippedSousThemes = 0;
        $lineCount = 0;
        
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            $lineCount++;
            
            // Nettoyer les données
            $themeName = isset($data[0]) ? trim($data[0]) : '';
            $sousThemeName = isset($data[1]) ? trim($data[1]) : '';
            
            // Ignorer si pas de nom de Catégories
            if ($themeName === '') {
                continue;
            }
            
            // Vérifier si le Catégories existe déjà pour cet utilisateur
            $existingTheme = $themesRepository->findOneBy(['name' => $themeName, 'user' => $user]);
            
            if (!$existingTheme) {
                // Créer le nouveau Catégorie
                $theme = new Themes();
                $theme->setName($themeName);
                if ($user instanceof User) {
                    $theme->setUser($user);
                }
                $em->persist($theme);
                $em->flush(); // Flush immédiatement pour obtenir l'ID
                $importedThemes++;
            } else {
                $theme = $existingTheme;
                $skippedThemes++;
            }
            
            // Si un sous-Catégorie est spécifié ET non vide
            if (!empty($sousThemeName)) {
                // Vérifier si le sous-Catégorie existe déjà pour ce Catégorie spécifique
                $existingSousTheme = $em->getRepository(SousThemes::class)
                    ->findOneBy(['name' => $sousThemeName, 'theme' => $theme]);
                
                if (!$existingSousTheme) {
                    // Créer le nouveau sous-Catégorie lié au Catégorie
                    $sousTheme = new SousThemes();
                    $sousTheme->setName($sousThemeName);
                    $sousTheme->setTheme($theme); // Association avec le Catégorie via theme_id
                    $em->persist($sousTheme);
                    $importedSousThemes++;
                } else {
                    $skippedSousThemes++;
                }
            }
        }
        
        // Flush final pour sauvegarder tous les sous-Catégories
        $em->flush();
        fclose($handle);
        
        $message = sprintf(
            'Import terminé : %d lignes lues, %d catégories ajoutées (%d ignorés), %d sous-Catégories ajoutées (%d ignorés)',
            $lineCount,
            $importedThemes,
            $skippedThemes,
            $importedSousThemes,
            $skippedSousThemes
        );
        
        $this->addFlash('success', $message);
        
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de l\'import : ' . $e->getMessage());
    }
    
    return $this->redirectToRoute('admin_themes');
}
   
    #[Route('/admin/themes/delete-theme/{id}', name: 'admin_delete_theme', methods: ['POST'])]
    public function deleteTheme(int $id, EntityManagerInterface $em, ThemesRepository $themesRepository): Response
    {
        $theme = $themesRepository->find($id);
        if ($theme) {
            // Vérifier si l'utilisateur est propriétaire du Catégories
            $user = $this->getUser();
            if ($theme->getUser() !== $user) {
                $this->addFlash('error', 'Vous n\'avez pas le droit de supprimer cette Catégorie.');
                return $this->redirectToRoute('admin_edit_theme', ['id' => $id]);
            }
            
            // Vérifier s'il y a des sous-Catégories associés
            $sousThemes = $em->getRepository(SousThemes::class)->findBy(['theme' => $theme]);
            if (count($sousThemes) > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette Catégorie car elle contient des sous-Catégories. Supprimez d\'abord toutes les sous-Catégories.');
                return $this->redirectToRoute('admin_edit_theme', ['id' => $id]);
            }
            
            // Vérifier s'il y a des livres associés (ajustez selon votre entité Livre)
            // Remplacez 'Livres' par le nom de votre entité livre et 'theme' par le nom de votre propriété
            $livres = $em->getRepository(Books::class)->findBy(['theme' => $theme]);
            if (count($livres) > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette Catégorie car des livres y sont associés. Supprimez d\'abord tous les livres ou changez leur Catégorie.');
                return $this->redirectToRoute('admin_edit_theme', ['id' => $id]);
            }
            
            // Si aucune contrainte, procéder à la suppression
            $em->remove($theme);
            $em->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Catégorie non trouvée.');
        }
        return $this->redirectToRoute('admin_themes');
    }
    #[Route('/admin/themes/delete-sous-theme/{id}', name: 'admin_delete_sous_theme', methods: ['POST'])]
public function deleteSousTheme(int $id, EntityManagerInterface $em, SousThemesRepository $sousThemesRepository): Response
{
        $sousTheme = $sousThemesRepository->find($id);
        if ($sousTheme) {
            // Vérifier si l'utilisateur est propriétaire du Catégories parent
            $user = $this->getUser();
            if ($sousTheme->getTheme()->getUser() !== $user) {
                $this->addFlash('error', 'Vous n\'avez pas le droit de supprimer cette sous-Catégorie.');
                return $this->redirectToRoute('admin_edit_sous_theme', ['id' => $id]);
            }
            
            // Vérifier s'il y a des livres associés à ce sous-Catégories
            $livres = $em->getRepository(Books::class)->findBy(['sousTheme' => $sousTheme]);
            if (count($livres) > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette sous-Catégorie car des livres y sont associés. Supprimez d\'abord tous les livres ou changez leur sous-Catégorie.');
                return $this->redirectToRoute('admin_edit_sous_theme', ['id' => $id]);
            }
            
            // Si aucune contrainte, procéder à la suppression
            $em->remove($sousTheme);
            $em->flush();
            $this->addFlash('success', 'Sous-Catégorie supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Sous-Catégorie non trouvée.');
        }
        return $this->redirectToRoute('admin_themes');
    }
}
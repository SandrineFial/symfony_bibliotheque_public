<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Books;
use App\Entity\Themes;
use App\Entity\SousThemes;
use App\Form\BookFormType;
use App\Repository\BooksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BookController extends AbstractController
{
    private const BOOK_INDEX_TEMPLATE = 'book/index.html.twig';
    
    /**
     * @return array<int, Themes>
     */
    private function getThemesForUser(User $user, ManagerRegistry $doctrine): array
    {
        $themes = $doctrine->getRepository(Themes::class)->findBy(['user' => $user]);
        
        // Trier les thèmes avec Collator
        $collator = new \Collator('fr_FR');
        usort($themes, function($a, $b) use ($collator) {
            return $collator->compare($a->getName(), $b->getName());
        });
        
        return $themes;
    }
    
    /**
     * @param array<int, Themes> $themes
     * @return array<int, int>
     */
    private function getBookCountsForThemes(array $themes, BooksRepository $booksRepository, User $user): array
    {
        $bookCounts = [];
        foreach ($themes as $theme) {
            $bookCounts[$theme->getId()] = count($booksRepository->findBy(['theme' => $theme, 'user' => $user]));
        }
        return $bookCounts;
    }
    
    /**
     * @return array{0: array<int, SousThemes>, 1: array<int, int>}
     */
    private function getSousThemesWithCounts(Themes $theme, EntityManagerInterface $entityManager, User $user): array
    {
        $sousThemes = $entityManager->getRepository(SousThemes::class)
            ->findBy(['theme' => $theme], ['name' => 'ASC']);
        
        $sousThemeBookCounts = [];
        foreach ($sousThemes as $sousTheme) {
            $count = $entityManager->getRepository(Books::class)
                ->count(['sousTheme' => $sousTheme, 'user' => $user]);
            $sousThemeBookCounts[$sousTheme->getId()] = $count;
        }
        
        return [$sousThemes, $sousThemeBookCounts];
    }
    
    /**
     * @param array<int, Books> $allBooks
     * @return array{0: array<int, Books>, 1: int, 2: int}
     */
    private function paginateBooks(array $allBooks, int $page, int $limit = 40): array
    {
        $totalBooks = count($allBooks);
        $totalPages = (int) ceil($totalBooks / $limit);
        $books = array_slice($allBooks, ($page - 1) * $limit, $limit);
        
        return [$books, $totalBooks, $totalPages];
    }
    
    /**
     * @return array<int, SousThemes>
     */
    private function getSortedSousThemesForTheme(?Themes $theme, ManagerRegistry $doctrine): array
    {
        if (!$theme) {
            return [];
        }
        
        $sousThemes = $doctrine->getRepository(SousThemes::class)->findBy(['theme' => $theme]);
        
        $collator = new \Collator('fr_FR');
        usort($sousThemes, function($a, $b) use ($collator) {
            return $collator->compare($a->getName(), $b->getName());
        });
        
        return $sousThemes;
    }
    
    private function buildReturnLink(Request $request): string
    {
        $type = $request->query->get('type');
        $q = $request->query->get('q');
        $page = $request->query->get('page');
        $theme_get = $request->query->get('theme');
        $currentSousThemeId = $request->query->get('soustheme');
        
        $url = $this->generateUrl('app_book');
        
        if ($type !== null && $q !== null && $page !== null) {
            $url = $this->generateUrl('app_book', [
                'type' => $type,
                'q' => $q,
                'page' => $page
            ]);
        } elseif ($theme_get !== null && $currentSousThemeId !== null) {
            $url = $this->generateUrl('books_by_sous_theme', [
                'themeId' => $theme_get,
                'sousThemeId' => $currentSousThemeId
            ]);
        } elseif ($theme_get !== null) {
            $url = $this->generateUrl('books_by_theme', ['id' => $theme_get]);
        }
        
        return $url;
    }
    
    /**
     * @return array<string, mixed>
     */
    private function processSessionEditData(Request $request, Books $book): array
    {
        $session = $request->getSession();
        $editData = $session->get('book_edit_data');
        $oldValues = [];
        
        if (!$editData) {
            return $oldValues;
        }
        
        if ($editData['theme'] && $book->getTheme() && $editData['theme'] != $book->getTheme()->getId()) {
            $oldValues['theme'] = $book->getTheme()->getName();
        }
        if ($editData['sousTheme'] && $book->getSousTheme() && $editData['sousTheme'] != $book->getSousTheme()->getId()) {
            $oldValues['sousTheme'] = $book->getSousTheme()->getName();
        }
        
        foreach (['isbn','commentaire', 'resume', 'type', 'edition', 'editionDetail', 'annees', 'etat', 'nbpages', 'aQui'] as $field) {
            $getter = 'get' . ucfirst($field);
            $old = $book->$getter();
            $new = $editData[$field] ?? null;

            if (($new === null || $new === '') && $old !== null) {
                $editData[$field] = $old;
            }
            if ($old !== null && $new !== null && $old != $new) {
                $oldValues[$field] = $old;
            }
        }
        
        $session->remove('book_edit_data');
        
        return $oldValues;
    }

    #[Route('/book', name: 'app_book')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!$user instanceof User) {
            throw new \LogicException('User must be an instance of App\\Entity\\User');
        }
        
        $themes = $this->getThemesForUser($user, $doctrine);
        /** @var BooksRepository $booksRepo */
        $booksRepo = $doctrine->getRepository(Books::class);
        $bookCounts = $this->getBookCountsForThemes($themes, $booksRepo, $user);
        
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('q', '');
        $search_type = $request->query->get('type', '');
        
        $allBooks = $booksRepo->searchBooks($search, $user, $search_type);
        [$books, $totalBooks, $totalPages] = $this->paginateBooks($allBooks, $page);

        // compte le nb d'auteurs différents
        $uniqueAuthors = count(array_unique(array_map(fn($book) => $book->getAuteur(), $allBooks)));

        return $this->render(self::BOOK_INDEX_TEMPLATE, [
            'controller_name' => 'HomeController',
            'themes' => $themes,
            'bookCounts' => $bookCounts,
            'books' => $books,
            'totalBooks' => $totalBooks,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'q' => $search,
            'nbauteurs' => $uniqueAuthors,
            'search_type' => $search_type
        ]);
    }

    #[Route('/books/theme/{id}', name: 'books_by_theme')]
    public function booksByTheme(Themes $theme, BooksRepository $booksRepository, ManagerRegistry $doctrine, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }
        
        if ($theme->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce thème.');
        }
        
        $themes = $this->getThemesForUser($user, $doctrine);
        $bookCounts = $this->getBookCountsForThemes($themes, $booksRepository, $user);
        [$sousThemes, $sousThemeBookCounts] = $this->getSousThemesWithCounts($theme, $entityManager, $user);
        
        $q = $request->query->get('q');
        $page = max(1, (int)$request->query->get('page', 1));
        
        $allBooks = $booksRepository->searchBooks($q, $user, null, $theme);
        [$books, $totalBooks, $totalPages] = $this->paginateBooks($allBooks, $page);
        
        return $this->render(self::BOOK_INDEX_TEMPLATE, [
            'controller_name' => 'BookController',
            'books' => $books,
            'theme' => $theme,
            'themes' => $themes,
            'bookCounts' => $bookCounts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'q' => $q,
            'sousThemes' => $sousThemes,
            'sousThemeBookCounts' => $sousThemeBookCounts,
            'currentThemeId' => $theme->getId(),
        ]);
    }

    #[Route('/books/sous-theme/{themeId}/{sousThemeId}', name: 'books_by_sous_theme')]
    public function booksByThemeSousTheme(int $themeId, int $sousThemeId, BooksRepository $booksRepository, ManagerRegistry $doctrine, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $theme = $entityManager->getRepository(Themes::class)->find($themeId);
        if (!$theme || $theme->getUser() !== $user) {
            throw $this->createNotFoundException('Thème introuvable ou non autorisé.');
        }

        $themes = $this->getThemesForUser($user, $doctrine);
        $bookCounts = $this->getBookCountsForThemes($themes, $booksRepository, $user);
        [$sousThemes, $sousThemeBookCounts] = $this->getSousThemesWithCounts($theme, $entityManager, $user);
        
        // Trouver le sous-thème spécifique
        $sousTheme = null;
        foreach ($sousThemes as $st) {
            if ($st->getId() === $sousThemeId) {
                $sousTheme = $st;
                break;
            }
        }
        
        if (!$sousTheme) {
            throw $this->createNotFoundException('Sous-thème introuvable.');
        }

        $q = $request->query->get('q');
        $page = max(1, (int)$request->query->get('page', 1));
        
        $allBooks = $booksRepository->searchBooks($q, $user, null, $theme, $sousTheme);
        [$books, $totalBooks, $totalPages] = $this->paginateBooks($allBooks, $page);

        return $this->render(self::BOOK_INDEX_TEMPLATE, [
            'controller_name' => 'BookController',
            'books' => $books,
            'theme' => $theme,
            'themes' => $themes,
            'bookCounts' => $bookCounts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'q' => $q,
            'sousThemes' => $sousThemes,
            'sousThemeBookCounts' => $sousThemeBookCounts,
            'currentThemeId' => $theme->getId(),
            'currentSousThemeId' => $sousTheme->getId(),
        ]);
    }

    #[Route('/book_add', name: 'app_book_add')]
public function book_add(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine): Response
{
    $user = $this->getUser();
    if (!$user) {
        throw $this->createAccessDeniedException('Vous devez être connecté.');
    }
    
    // Récupérer tous les thèmes de l'utilisateur
    $themes = $doctrine->getRepository(Themes::class)->findBy(['user' => $user]);
    
    // Trier les thèmes avec Collator pour gérer les accents
    $collator = new \Collator('fr_FR');
    usort($themes, function($a, $b) use ($collator) {
        return $collator->compare($a->getName(), $b->getName());
    });
    
    $form = $this->createForm(BookFormType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $book = $form->getData();
        $book->setUser($this->getUser());
        // Vérifier si un livre avec le même titre et auteur existe déjà pour cet utilisateur
        $existingBook = $entityManager->getRepository(Books::class)->findOneBy([
            'titre' => $book->getTitre(),
            'auteur' => $book->getAuteur(),
            'user' => $user
        ]);

        if ($existingBook) { // Stocker les nouvelles données en session
            $session = $request->getSession();
            $session->set('book_edit_data', [
                'theme' => $book->getTheme() ? $book->getTheme()->getId() : null,
                'sousTheme' => $book->getSousTheme() ? $book->getSousTheme()->getId() : null,
                'isbn' => $book->getIsbn(),
                'resume' => $book->getResume(),
                'commentaire' => $book->getCommentaire(),
                'type' => $book->getType(),
                'edition' => $book->getEdition(),
                'editionDetail' => $book->getEditionDetail(),
                'annees' => $book->getAnnees(),
                'etat' => $book->getEtat(),
                'nbpages' => $book->getNbPages(),
                'aQui' => $book->getAQui(),
                'flashmessage' => 'Ce livre existe déjà. Vous pouvez le modifier directement.'
            ]);
            // affiche flash info dans la session
            $this->addFlash('info', 'Ce livre existe déjà. Vous pouvez le modifier directement.');
            return $this->redirectToRoute('app_book_edit', ['id' => $existingBook->getId()]);
        }
        else {

            $entityManager->persist($book);
            $entityManager->flush();
            $this->addFlash('success', 'Livre ajouté avec succès');
            return $this->redirectToRoute('app_book');
        }
    }

    return $this->render('book/add.html.twig', [
        'controller_name' => 'BookController',
        'bookForm' => $form->createView(),
        'themes' => $themes,
    ]);
}
    #[Route('/book_add_csv', name: 'app_book_add_csv')]
    public function book_add_csv(): Response
    {
        return $this->render('book/add_csv.html.twig', [
            'controller_name' => 'BookController',
        ]);
    }
    // Modification d'un livre
    #[Route('/book_edit/{id}', name: 'app_book_edit')]
public function book_edit(Request $request, EntityManagerInterface $entityManager, Books $book, ManagerRegistry $doctrine): Response
{
    $user = $this->getUser();
    if (!$user) {
        throw $this->createAccessDeniedException('Vous devez être connecté.');
    }
    
    if ($book->getUser() !== $user) {
        throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce livre.');
    }
    
    $themes = $this->getThemesForUser($user, $doctrine);
    $sousThemes = $this->getSortedSousThemesForTheme($book->getTheme(), $doctrine);
    $lienretour = $this->buildReturnLink($request);
    
    $form = $this->createForm(BookFormType::class, $book);
    $oldValues = $this->processSessionEditData($request, $book);
    
    if (!empty($oldValues)) {
        $session = $request->getSession();
        $editData = $session->get('book_edit_data');
        $form->submit($editData, false);
        $lienretour = $this->generateUrl('app_book');
    }
    
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Livre modifié avec succès');
        return $this->redirect($lienretour);
    }

    return $this->render('book/edit.html.twig', [
        'controller_name' => 'BookController',
        'bookForm' => $form->createView(),
        'book' => $book,
        'themes' => $themes,
        'sousThemes' => $sousThemes,
        'selectedTheme' => $book->getTheme(),
        'selectedSousTheme' => $book->getSousTheme(),
        'lienretour' => $lienretour,
        'oldValues' => $oldValues,
    ]);
}
  
    #[Route('/book_delete/{id}', name: 'book_delete')]
    public function book_delete(EntityManagerInterface $entityManager, Books $book): Response
    {
        $entityManager->remove($book);
        $entityManager->flush();
        $this->addFlash('success', 'Livre supprimé avec succès');
        return $this->redirectToRoute('app_book');
    }
    
    /**
     * @param array<string> $data
     */
    private function isValidCsvLine(array $data): bool
    {
        return count($data) >= 3 && !empty($data[0]) && !empty($data[1]) && !empty($data[2]);
    }
    
    /**
     * @param array<string> $data
     * @param array<string, int> $themeIdCache
     * @param array<string, int> $sousThemeIdCache
     */
    private function createBookFromCsvData(
        array $data,
        User $user,
        EntityManagerInterface $entityManager,
        array &$themeIdCache,
        array &$sousThemeIdCache
    ): Books {
        $book = new Books();
        $book->setUser($user);
        $book->setAuteur($data[0]);
        $book->setTitre($data[1]);
        
        $this->attachThemeToBook($book, $data[2], $entityManager, $themeIdCache);
        $this->attachSousThemeToBook($book, $data[3] ?? null, $entityManager, $themeIdCache, $sousThemeIdCache);
        $this->setOptionalBookFields($book, $data);
        
        return $book;
    }
    
    /**
     * @param array<string, int> $themeIdCache
     */
    private function attachThemeToBook(
        Books $book,
        string $themeName,
        EntityManagerInterface $entityManager,
        array &$themeIdCache
    ): void {
        $themeKey = strtolower(trim($themeName));
        
        if (!isset($themeIdCache[$themeKey])) {
            $theme = $this->findOrCreateTheme($entityManager, $themeName);
            $themeIdCache[$themeKey] = $theme->getId();
        } else {
            $theme = $entityManager->getReference(Themes::class, $themeIdCache[$themeKey]);
        }
        
        $book->setTheme($theme);
    }
    
    /**
     * @param array<string, int> $themeIdCache
     * @param array<string, int> $sousThemeIdCache
     */
    private function attachSousThemeToBook(
        Books $book,
        ?string $sousThemeName,
        EntityManagerInterface $entityManager,
        array $themeIdCache,
        array &$sousThemeIdCache
    ): void {
        if (empty($sousThemeName)) {
            return;
        }
        
        $theme = $book->getTheme();
        $themeKey = strtolower(trim($theme->getName()));
        $sousThemeKey = $themeIdCache[$themeKey] . '|' . strtolower(trim($sousThemeName));
        
        if (!isset($sousThemeIdCache[$sousThemeKey])) {
            $sousTheme = $this->findOrCreateSousTheme($entityManager, $sousThemeName, $theme);
            $sousThemeIdCache[$sousThemeKey] = $sousTheme->getId();
        } else {
            $sousTheme = $entityManager->getReference(SousThemes::class, $sousThemeIdCache[$sousThemeKey]);
        }
        
        $book->setSousTheme($sousTheme);
    }
    
    /**
     * @param array<string> $data
     */
    private function setOptionalBookFields(Books $book, array $data): void
    {
        $book->setResume($data[4] ?? null);
        $book->setType($data[5] ?? null);
        $book->setEdition($data[6] ?? null);
        $book->setEditionDetail($data[7] ?? null);
        
        if (!empty($data[8])) {
            $book->setAnnees($data[8]);
        }
        
        $book->setEtat($data[9] ?? null);
        
        if (isset($data[10]) && is_numeric($data[10])) {
            $book->setNbPages((int)$data[10]);
        }
        
        if (!empty($data[11])) {
            $book->setAQui($data[11]);
        }
    }
    
    private function flushBatch(
        EntityManagerInterface $entityManager,
        int $importedCount,
        int $batchSize,
        int $userId
    ): ?User {
        if ($importedCount % $batchSize !== 0) {
            return null;
        }
        
        $entityManager->flush();
        $entityManager->clear();
        error_log("Traité: $importedCount livres");
        
        return $entityManager->getReference(User::class, $userId);
    }
    
    // import des livres depuis csv
   
#[Route('/book_import', name: 'app_book_import')]
public function book_import(Request $request, EntityManagerInterface $entityManager): Response
{
    $file = $request->files->get('csv_file');
    
    if (!$file || !$file->isValid()) {
        $this->addFlash('error', 'Fichier invalide ou manquant');
        return $this->redirectToRoute('app_home');
    }

    $user = $this->getUser();
    
    if (!$user instanceof User) {
        $this->addFlash('error', 'Vous devez être connecté pour importer des livres');
        return $this->redirectToRoute('app_login');
    }

    $userId = $user->getId();
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    try {
        $handle = fopen($file->getPathname(), 'r');
        
        if (!$handle) {
            throw new \Exception('Impossible d\'ouvrir le fichier');
        }

        $importedCount = 0;
        $errorCount = 0;
        $batchSize = 50;
        $themeIdCache = [];
        $sousThemeIdCache = [];

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $data = array_map('trim', $data);
            
            if (!$this->isValidCsvLine($data)) {
                $errorCount++;
                continue;
            }
            
            try {
                $book = $this->createBookFromCsvData($data, $user, $entityManager, $themeIdCache, $sousThemeIdCache);
                $entityManager->persist($book);
                $importedCount++;
                
                $newUser = $this->flushBatch($entityManager, $importedCount, $batchSize, $userId);
                if ($newUser) {
                    $user = $newUser;
                }
            } catch (\Exception $bookException) {
                $errorCount++;
            }
        }
        
        fclose($handle);
        
        if ($importedCount % $batchSize !== 0) {
            $entityManager->flush();
        }
        
        $message = "Import terminé : {$importedCount} livre(s) importé(s)";
        if ($errorCount > 0) {
            $message .= " ({$errorCount} erreur(s))";
        }
        $this->addFlash('success', $message);
        
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
        error_log('Erreur import CSV: ' . $e->getMessage());
    }
    
    return $this->redirectToRoute('app_home');
}

/**
 * Trouve ou crée un thème pour l'utilisateur connecté.
 */
private function findOrCreateTheme(EntityManagerInterface $entityManager, string $themeName): Themes
{
    $themeName = trim($themeName);
    $user = $this->getUser();
    
    if (!$user) {
        throw new \RuntimeException('Utilisateur non connecté');
    }
    
    // Recherche simple par nom et utilisateur
    $theme = $entityManager->getRepository(Themes::class)->findOneBy([
        'name' => $themeName,
        'user' => $user
    ]);
    
    if (!$theme) {
        $theme = new Themes();
        $theme->setName($themeName);
        if ($user instanceof User) {
            $theme->setUser($user);
        }
        $entityManager->persist($theme);
        $entityManager->flush();
    }
    
    return $theme;
}

/**
 * Trouve ou crée un sous-thème.
 */
private function findOrCreateSousTheme(EntityManagerInterface $entityManager, string $sousThemeName, Themes $theme): SousThemes
{
    $sousTheme = $entityManager->getRepository(SousThemes::class)->findOneBy([
        'name' => $sousThemeName,
        'theme' => $theme
    ]);
    
    if (!$sousTheme) {
        $sousTheme = new SousThemes();
        $sousTheme->setName($sousThemeName);
        $sousTheme->setTheme($theme);
        $entityManager->persist($sousTheme);
        $entityManager->flush();
    }
    
    return $sousTheme;
}

}
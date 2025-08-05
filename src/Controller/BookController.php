<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Books;
use App\Entity\Themes;
use App\Form\BookFormType;
use App\Repository\BooksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;

final class BookController extends AbstractController
{
    #[Route('/book', name: 'app_book')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        if($user == null) {
            return $this->redirectToRoute('app_login');
        }
        $themes = $doctrine->getRepository(Themes::class)->findBy([], ['name' => 'ASC']);
        $booksRepo = $doctrine->getRepository(Books::class);
        $q = $request->query->get('q');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 10;
        $allBooks = $booksRepo->searchBooks($q, $user);
        $totalBooks = count($allBooks);
        $pages = (int) ceil($totalBooks / $limit);
        $books = array_slice($allBooks, ($page - 1) * $limit, $limit);
        foreach ($themes as $theme) {
            $theme->bookCount = count($booksRepo->findBy(['theme' => $theme, 'user' => $user]));
        }
        return $this->render('book/index.html.twig', [
            'controller_name' => 'HomeController',
            'themes' => $themes,
            'books' => $books,
            'totalBooks' => $totalBooks,
            'currentPage' => $page,
            'totalPages' => $pages,
            'q' => $q,
        ]);
    }

    #[Route('/book_add', name: 'app_book_add')]
    public function book_add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $book = $form->getData();
            $book->setUser($this->getUser());
            //$book->setTheme($this->getTheme());
            $entityManager->persist($book);
            $entityManager->flush();
            return new Response('Livre ajouté avec succès');
        }

        return $this->render('book/add.html.twig', [
            'controller_name' => 'BookController',
            'bookForm' => $form->createView(),
        ]);
    }

    #[Route('/book_edit/{id}', name: 'app_book_edit')]
    public function book_edit(Request $request, EntityManagerInterface $entityManager, Books $book): Response
    {
        $form = $this->createForm(BookFormType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_home');
        }

        return $this->render('book/edit.html.twig', [
            'controller_name' => 'BookController',
            'bookForm' => $form->createView(),
        ]);
    }

    #[Route('/books/theme/{id}', name: 'books_by_theme')]
    public function booksByTheme(Themes $theme, BooksRepository $booksRepository, ManagerRegistry $doctrine, Request $request): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }
        $themes = $doctrine->getRepository(Themes::class)->findBy([], ['name' => 'ASC']);
        foreach ($themes as $t) {
            $t->bookCount = count($booksRepository->findBy(['theme' => $t, 'user' => $user]));
        }
        $q = $request->query->get('q');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 10;
        $allBooks = $booksRepository->searchBooks($q, $user, $theme);
        $totalBooks = count($allBooks);
        $pages = (int) ceil($totalBooks / $limit);
        $books = array_slice($allBooks, ($page - 1) * $limit, $limit);
        return $this->render('book/index.html.twig', [
            'controller_name' => 'BookController',
            'books' => $books,
            'theme' => $theme,
            'themes' => $themes,
            'currentPage' => $page,
            'totalPages' => $pages,
            'q' => $q,
        ]);
    }
}
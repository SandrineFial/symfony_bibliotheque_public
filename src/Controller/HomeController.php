<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ManagerRegistry $doctrine): Response
    {
        // verif si connecte
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        else {
            // Redirige vers la page d'index des livres
            return $this->redirectToRoute('app_book');
        }
    }
}
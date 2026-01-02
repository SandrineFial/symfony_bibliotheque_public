<?php

namespace App\Service;

use App\Entity\Books;
use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfGeneratorService
{
    public function generateSearchResultsPdf(
        array $books,
        string $searchQuery = '',
        string $searchType = '',
        int $totalBooks = 0,
        int $uniqueAuthors = 0
    ): string {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsPhpEnabled(true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->generateHtmlContent($books, $searchQuery, $searchType, $totalBooks, $uniqueAuthors);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }
    
    private function generateHtmlContent(
        array $books,
        string $searchQuery,
        string $searchType,
        int $totalBooks,
        int $uniqueAuthors
    ): string {
        $searchTypeName = $this->getSearchTypeName($searchType);
        $searchSummary = $this->buildSearchSummary($searchQuery, $searchTypeName, $totalBooks, $uniqueAuthors);
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Résultats de recherche - Bibliothèque</title>
            <style>
                body { 
                    font-family: DejaVu Sans, Arial, sans-serif; 
                    font-size: 11px;
                    line-height: 1.3;
                    color: #333;
                    margin: 15px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #6f42c1;
                    padding-bottom: 10px;
                }
                .header h1 {
                    color: #6f42c1;
                    font-size: 20px;
                    margin: 0 0 8px 0;
                }
                .search-info {
                    background-color: #f8f9fa;
                    padding: 12px;
                    border-left: 3px solid #6f42c1;
                    margin-bottom: 15px;
                    font-size: 10px;
                }
                .search-info h2 {
                    color: #6f42c1;
                    font-size: 14px;
                    margin: 0 0 8px 0;
                }
                .search-info p {
                    margin: 3px 0;
                    font-size: 10px;
                }
                .books-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                .books-table th {
                    background-color: #6f42c1;
                    color: white;
                    padding: 8px 6px;
                    text-align: left;
                    font-size: 11px;
                    font-weight: bold;
                }
                .books-table td {
                    padding: 6px;
                    border-bottom: 1px solid #e9ecef;
                    font-size: 10px;
                    vertical-align: top;
                }
                .books-table tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                .book-title {
                    font-weight: bold;
                    color: #495057;
                }
                .book-author {
                    color: #6c757d;
                }
                .footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 9px;
                    color: #6c757d;
                    border-top: 1px solid #e9ecef;
                    padding-top: 8px;
                }
                .no-books {
                    text-align: center;
                    color: #6c757d;
                    font-style: italic;
                    margin: 20px 0;
                }
                .col-title { width: 55%; }
                .col-author { width: 35%; }
                .col-number { width: 10%; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Ma Bibliothèque en ligne</h1>
                <p>Liste des livres</p>
            </div>
            
            <div class="search-info">
                <h2>Récapitulatif</h2>
                ' . $searchSummary . '
            </div>';
                
        if (empty($books)) {
            $html .= '<div class="no-books">Aucun livre trouvé pour cette recherche.</div>';
        } else {
            $html .= '<table class="books-table">
                        <thead>
                            <tr>
                                <th class="col-number">#</th>
                                <th class="col-title">Titre</th>
                                <th class="col-author">Auteur</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            foreach ($books as $index => $book) {
                $html .= $this->generateBookTableRow($book, $index + 1);
            }
            
            $html .= '</tbody></table>';
        }
        
        $html .= '<div class="footer">
                <p>Généré le ' . date('d/m/Y à H:i') . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function generateBookTableRow(Books $book, int $number): string
    {
        // Utilisation de strip_tags et html_entity_decode pour nettoyer les chaînes
        $title = $this->cleanString($book->getTitre() ?? 'Titre non spécifié');
        $author = $this->cleanString($book->getAuteur() ?? 'Auteur non spécifié');
        
        return '<tr>
                    <td>' . $number . '</td>
                    <td class="book-title">' . $title . '</td>
                    <td class="book-author">' . $author . '</td>
                </tr>';
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
    
    private function getSearchTypeName(string $searchType): string
    {
        return match ($searchType) {
            'titre' => 'titre',
            'auteur' => 'auteur',
            'edition' => 'édition',
            default => 'tous les champs'
        };
    }
    
    private function buildSearchSummary(
        string $searchQuery,
        string $searchTypeName,
        int $totalBooks,
        int $uniqueAuthors
    ): string {
        $summary = '';
        
        if (!empty($searchQuery)) {
            $summary .= '<p><strong>Terme recherché :</strong> "' . htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') . '"</p>';
            $summary .= '<p><strong>Type de recherche :</strong> ' . $searchTypeName . '</p>';
        } else {
            $summary .= '<p><strong>Recherche :</strong> Tous les livres</p>';
        }
        
        $summary .= '<p><strong>Nombre de livres :</strong> ' . $totalBooks . '</p>';
        $summary .= '<p><strong>Nombre d\'auteurs différents :</strong> ' . $uniqueAuthors . '</p>';
        
        return $summary;
    }
}

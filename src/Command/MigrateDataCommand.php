<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(name: 'app:migrate-data')]
class MigrateDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Configuration source (changez selon votre cas)
            $sourceConfig = [
                'driver' => 'pdo_pgsql',  // ou 'pdo_mysql' si source MySQL
                'host' => '127.0.0.1',
                'port' => 5432,           // ou 3306 pour MySQL
                'dbname' => 'bibliotheque',
                'user' => 'biblio',
                'password' => 'MpBiblio2025Roudezet',
            ];

            $output->writeln('Connexion à la base source...');
            $sourceConnection = DriverManager::getConnection($sourceConfig);

            // Tester la connexion
            $sourceConnection->connect();
            
            // Migrer les utilisateurs
            $output->writeln('Migration des utilisateurs...');
            $users = $sourceConnection->fetchAllAssociative('SELECT * FROM "user"'); // Guillemets pour PostgreSQL
            foreach ($users as $user) {
                $this->entityManager->getConnection()->insert('user', $user);
            }

            // Migrer les thèmes
            $output->writeln('Migration des thèmes...');
            $themes = $sourceConnection->fetchAllAssociative('SELECT * FROM themes');
            foreach ($themes as $theme) {
                $this->entityManager->getConnection()->insert('themes', $theme);
            }

            // Migrer les livres
            $output->writeln('Migration des livres...');
            $books = $sourceConnection->fetchAllAssociative('SELECT * FROM books');
            foreach ($books as $book) {
                $this->entityManager->getConnection()->insert('books', $book);
            }

            $output->writeln('Migration terminée avec succès !');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('Erreur lors de la migration : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
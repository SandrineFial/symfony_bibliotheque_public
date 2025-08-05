<?php

namespace App\Command;

use App\Entity\Themes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ListThemes',
    description: 'Liste tous les thèmes de la base de données',
)]
class ListThemesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Liste tous les thèmes de la base de données.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer tous les thèmes
        $themes = $this->entityManager->getRepository(Themes::class)->findAll();

        if (empty($themes)) {
            $io->warning('Aucun thème trouvé dans la base de données.');
            return Command::SUCCESS;
        }

        // Afficher les thèmes
        $io->title('Liste des thèmes :');
        foreach ($themes as $theme) {
            $io->writeln(sprintf(
                'ID: %d | Nom: %s | Couleur: %s',
                $theme->getId(),
                $theme->getName(),
                $theme->getColor()
            ));
        }

        $io->success('Tous les thèmes ont été listés avec succès.');
        return Command::SUCCESS;
    }
}
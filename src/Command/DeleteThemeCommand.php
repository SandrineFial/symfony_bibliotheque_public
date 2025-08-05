<?php

namespace App\Command;

use App\Entity\Themes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:DeleteTheme',
    description: 'Supprime un thème de la base de données par son ID',
)]
class DeleteThemeCommand extends Command
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
            ->addArgument('id', InputArgument::REQUIRED, 'ID du thème à supprimer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument('id');

        // Récupérer le thème par son ID
        $theme = $this->entityManager->getRepository(Themes::class)->find($id);

        if (!$theme) {
            $io->error(sprintf('Aucun thème trouvé avec l\'ID %d.', $id));
            return Command::FAILURE;
        }

        // Supprimer le thème
        $this->entityManager->remove($theme);
        $this->entityManager->flush();

        $io->success(sprintf('Le thème avec l\'ID %d a été supprimé avec succès.', $id));

        return Command::SUCCESS;
    }
}
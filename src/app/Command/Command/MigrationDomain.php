<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Manager\SheetManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputInterface,
    Output\OutputInterface,
    Style\SymfonyStyle as style,
    Input\InputArgument
} ;


/**
 * Cette commande permet d'archiver automatiquement 
 * pour les supprimer de la vue utilisateur et la basculer en Archive
 *
 * @author HGDDEDBN
 */
#[AsCommand(name: 'app:migrate:domain')]
class MigrationDomain extends Command {

    private $sheetRepository;

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @see InputInterface::bind()
     * @see InputInterface::validate()
     */
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->sheetRepository = new SheetManager();
    }

    /**
     * 
     * @return void
     */
    protected function configure(): void {
        $this
                ->setDescription('Cette commande permet de migrer les domaines netachat')
                ->addArgument('type', InputArgument::REQUIRED, 'Le type de fiche marchée?')
                ;
    }

    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new style($input, $output);
        $io->title('Migration en cours ... ');
        
        $type = $input->getArgument("type");

        if (!$type){
              $output->writeln([
                "",
                "<info>le type de fiche marchée est obligatoire</>",
            ]);
        }

          
        // le chargement de la mise a jour des fiches marché
        $count =  $this->sheetRepository->migrateDomain($type); // Récupérer les ids et les dates des fiches
        
        $io->success("Migration terminée !") ;
        
        $output->writeln([
            "",
            "<info>{$count} fichier(s) migrée(s)!</>",
        ]);
        
        // Retourné l'etat de la commande
        return Command::SUCCESS  ;
    }

}

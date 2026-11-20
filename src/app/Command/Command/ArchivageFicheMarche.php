<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Repository\SheetRepository;
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
#[AsCommand(name: 'app:archivage:start')]
class ArchivageFicheMarche extends Command {

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
        $this->sheetRepository = new SheetRepository();
    }

    /**
     * 
     * @return void
     */
    protected function configure(): void {
        $this
                ->setDescription('Cette commande permet d\'archiver les fiches marchés')
                ->setHelp('Cette commande permet d\'archiver automatiquement pour les supprimer de la vue utilisateur et la basculer en Archive')
                ->addArgument('nbr_jours', InputArgument::OPTIONAL, 'Le nombre de jours de décalage svp ?')
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
        $io->title('Archivage en cours ... ');
        
        $nbrJours = intval($input->getArgument("nbr_jours"));

        if (!$nbrJours): // Si le nombre des jours n'est pas mis en paramteres
            $nbrJours = 25; // Affecter 30 par defaut
        endif;
        
        // le chargement de la mise a jour des fiches marché
        $nbrFicheArchive = $this->sheetRepository->archiverByEndDatePlus($nbrJours); // Récupérer les ids et les dates des fiches
        $io->success("Archivage terminé !") ;
        
        $output->writeln([
            "",
            "<info>{$nbrFicheArchive} fichier(s) archivé(s)!</>",
        ]);
        
        // Retourné l'etat de la commande
        return Command::SUCCESS  ;
    }

}

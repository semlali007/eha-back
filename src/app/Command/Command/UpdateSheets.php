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
 */
#[AsCommand(name: 'app:sheets:update')]
class UpdateSheets extends Command {

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
                ->setDescription('Cette commande permet de mettre à jour les dates fin de fiches marchées')
                ->addArgument('fiche_id', InputArgument::OPTIONAL, ' l\' id d\'une fiche marchée.')
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
        $ficheId = 0;
        $io->title('Mise à jour en cours ... ');
        if($input->getArgument("fiche_id")){
            $ficheId = $input->getArgument("fiche_id");
        }
        
      
        
        // le chargement de la mise a jour des fiches marché
        $sheetSheet = $this->sheetRepository->updateSheetEndDate($ficheId); // Récupérer les ids et les dates des fiches
        $io->success("Mise à jour terminée!") ;
        
        $output->writeln([
            "",
            "<info>{$sheetSheet} fiche(s) modifiée(s)!</>",
        ]);
        
        // Retourné l'etat de la commande
        return Command::SUCCESS  ;
    }

}

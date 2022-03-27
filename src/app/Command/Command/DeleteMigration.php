<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Manager\SheetManager;
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
class DeleteMigration extends Command {

    // le nom de la commande (s'écrit apres le "bin/console")
    protected static $defaultName = 'app:migrate:delete';
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
                ->setDescription('Cette commande permet supprimer les fiches netAchat')
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
        $io->title('Suppression en cours ... ');
        // le chargement de la mise a jour des fiches marché
        $netachat =  $this->sheetRepository->getNetAchatSheet(); // Récupérer les ids et les dates des fiches
        foreach($netachat as $n){
            if(is_array($n) && is_numeric($n['netachat_id']) && $n['netachat_id']>0){
                $this->sheetRepository->remove($n['id']);
            }
        }

        $io->success("Migration terminée !") ;
        
        $output->writeln([
            "",
            "<info>fiches netachat supprimées!</>",
        ]);
        
        // Retourné l'etat de la commande
        return Command::SUCCESS  ;
    }

}

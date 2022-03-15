<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Repository\NotificationsRepository;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputInterface,
    Output\OutputInterface,
    Style\SymfonyStyle as style,
} ;


/**
 * Cette commande permet d'archiver automatiquement 
 * pour les supprimer de la vue utilisateur et la basculer en Archive
 *
 * @author HGDDEDBN
 */
class RemoveExpiredNotifications extends Command {

    // le nom de la commande (s'écrit apres le "bin/console")
    protected static $defaultName = 'app:auto-remove:notifs';
    private $notificationsRepository;

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
        $this->notificationsRepository = new NotificationsRepository();
    }

    /**
     * 
     * @return void
     */
    protected function configure(): void {
        $this
                ->setDescription('Cette commande permet de supprimer automatiquement les notifications')
                ->setHelp('Cette commande Cette commande permet de supprimer automatiquement les notifications')
                
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
         
        $nbrJours = 30; // Suppression par 30 par defaut
        
        // le chargement de la mise a jour des fiches marché
        $notifs = $this->notificationsRepository->autoRemoveNotifsAfter($nbrJours); // Récupérer les ids et les dates des fiches
        foreach ($notifs as $notif) {
            $this->notificationsRepository->updateNotified($notif["id"]) ;
        }
        $nbrNotifsRemoved = count($notifs) ;
        $io->success("Suppression terminé !") ;
        
        $output->writeln([
            "",
            "<info>{$nbrNotifsRemoved} notification(s) supprimée(s)!</>",
        ]);
        
        // Retourné l'etat de la commande
        return Command::SUCCESS  ;
    }

}

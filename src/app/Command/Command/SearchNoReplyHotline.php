<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Repository\Hotline\HotlineRepository ;
use App\Services\StaticTextService ;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputInterface,
    Output\OutputInterface,
    Style\SymfonyStyle as style,
    Input\InputArgument
} ;


/**
 * @author HGDDEDBN
 */
class SearchNoReplyHotline extends Command {

    // le nom de la commande (s'écrit apres le "bin/console")
    protected static $defaultName = 'app:search:no-reply';
    private $hotlineRepository;

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
        $this->hotlineRepository = new HotlineRepository();
    }

    /**
     * 
     * @return void
     */
    protected function configure(): void {
        $this
                ->setDescription('Cette commande permet de chercher les discussions et notifier les hotlineurs')
                ->setHelp('Cette commande permet de chercher les discussions et notifier les hotlineurs')
                
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
        $io->title('Recherche en cours ... ');
        $i = 0 ;
        $array= $this->hotlineRepository->listAll(); // Récupérer les ids et les dates des fiches
        foreach ($array as $question) {
           
            $checkIsCommented = $this->hotlineRepository->getNotAnswered($question['id']) ;
            if(!$checkIsCommented){
                $i++ ;
                 $subject = "Achatpédia - Q{$question['id']} "; // ! probleme encodage coté serveur
                (new Mailer())
                        ->setSubject(utf8_decode($subject)) // mettre le sujet
                        ->setRecepients($emailTo) // Recepteur
                        ->html("mail/hotline_question_creation_to_hotliner.html.twig", [
                            'id' => $question['id'],
                            'sha_hotline' => $sha_hotline,
                            'message' => StaticTextService::$_HOTLINE_TEXT['06'],
                            'config_domain' => getenv('CONFIG_DOMAINE')                           
                        ])
                        ->send() // Envoyer l'email
                ;
            }
        }
        $io->success("Recherche et notifications terminé !") ;
        
        $output->writeln([
            "",
            "<info>{$i} utilisateurs(s) notifié(s)!</>",
        ]);
        
        // Retourné l'etat de la commande
        return Command::SUCCESS  ;
    }

}

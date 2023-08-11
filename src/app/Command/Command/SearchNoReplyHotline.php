<?php

declare(strict_types=1);

namespace App\Command\Command;

use App\Repository\Hotline\HotlineRepository ;
use Core\Mailer\Builder ;
use Core\Util\Date as dateHotline;
use App\Services\StaticTextService ;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputInterface,
    Output\OutputInterface,
    Style\SymfonyStyle as style
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

        $discussions= $this->hotlineRepository->listAll(); // Récupérer les ids et les dates des fiches
        foreach ($discussions as $question) {
            $i++;
            $isAnswered = $this->hotlineRepository->getNotAnswered($question['id']);
            if (!$isAnswered): // Pas de commentaires
                $dateStr = explode(" ", $question['quand']);
                $notifNumber = intval($question['notified']) ;
                $this->notifyByEmail(
                        $question['email'], $dateStr[0],$question['id'], $question['sha_hotline']
                        ,$notifNumber
                        ) ;
            endif;
        }
        
        $io->success("Recherche et notifications terminé !") ;
        $output->writeln([
            "",
            "<info>{$i} utilisateur(s) notifié(s)!</>",
        ]);

        // Retourné l'etat de la commande
        return Command::SUCCESS  ;
    }
    
    /**
     * 
     * @param string $emailTo
     * @param string $date
     * @param int $id
     * @param string $label
     * @param string $theme
     * @param string $sha_hotline
     * @param int $notifNumber
     */
    public function notifyByEmail(string $emailTo,string $date, int $id,string $label, string $theme, string $sha_hotline, int $notifNumber = 0):void{
        // Rappel num 1=> 5
        $dateM5 = new \DateTime(dateHotline::generateDateMoins(5,'Y-m-d')) ;
        $dateM15 = new \DateTime(dateHotline::generateDateMoins(15,'Y-m-d')) ;
        $dateM30 = new \DateTime(dateHotline::generateDateMoins(30,'Y-m-d')) ;
        $dateTime =  new \DateTime($date) ;
        
        if($notifNumber == 0 && ($dateM5)>= $dateTime && ($dateTime> $dateM15)){
           $this->sendMail($emailTo,$id,$label,$theme, $sha_hotline) ;
           $this->hotlineRepository->updateNotif() ;
        }
        
        // Rappel Num 2 => 15
        if($notifNumber == 1 && ($dateM15)>= $dateTime && ($dateTime> $dateM30)){
           $this->sendMail($emailTo,$id,$label,$theme, $sha_hotline) ;
           $this->hotlineRepository->updateNotif() ;
        }
         // Rappel num 3 => 30
         if($notifNumber > 1 &&  ($dateTime<= $dateM30)){
           $this->sendMail($emailTo,$id,$sha_hotline) ;
           $this->hotlineRepository->updateNotif() ;
        }
    }
    
    /**
     * 
     * @param string $emailTo
     */
    public function sendMail(string $emailTo, int $id, string $sha_hotline):void {
        $env = getenv('INSTALL_ENV');
        // le sujet de l'e-mail en fonction de l'env
        if ($env === 'PROD') {
            $prefix = 'P';
            $envLabel = 'Production';
        } elseif ($env === 'PREPROD') {
            $prefix = 'PP';
            $envLabel = 'Preproduction';
        } else {
            $prefix = 'DEV';
            $envLabel = 'Developpement';
        }
        (new Builder())
                ->setSubject("Achatpedia - Q" . $prefix . $id . '-' . $envLabel . '-' . "Rappel")->setRecepients($emailTo) 
                ->html("mail/hotline_question_creation_to_hotliner.html.twig", [
                    'id' => $id,
                    'sha_hotline' => $sha_hotline,
                    'message' => StaticTextService::$_HOTLINE_TEXT['06'],
                    'config_domain' => getenv('CONFIG_DOMAINE')
                ])->send()  
        ;
    }

}

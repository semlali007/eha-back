<?php

declare(strict_types=1);

namespace App\Command\Command;

 
use App\Repository\Hotline\HotlineRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\{
    Helper\Table,
    Command\Command,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Style\SymfonyStyle as style,
} ;

/**
 * Cette commande permet de supprimer automatiquement les demandes aprés une durée 
 * @author HGDDEDBN
 */
#[AsCommand(name: 'app:hotline:remove')]
class AutoClotureDemandeHotline extends Command {

    private HotlineRepository $hotlineRepository;
 
    
    const DEFAULT_END_DAYS = 30 ;
    const DEFAULT_start_DAYS = 0 ;
    
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
                ->setDescription('Cette commande permet d\'alerter les utilisateurs avant la fin de validité des fiches marchés')
                ->setHelp('Cette commande permet d\'alerter les utilisateurs avant la fin de validité des fiches marchés')
                ->addOption(
                        'nbr',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Le nombre de jours aprés la date de fin des fiches marchés',
                        self::DEFAULT_END_DAYS // this is the new default value, instead of null
                )
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
        $io->title('Recherche des demandes non cloturées en cours ... ');

        $nombre = intval($input->getOption("nbr")); // Option 1: nombre de date DateFin - (J)
       
        // le chargement de la mise a jour des fiches marché
        $hotlines = $this->hotlineRepository->cloturerDemandes($nombre) ;
         
        $nbrDemandes = count($hotlines); // Récupérer les ids et les dates des fiches
        
        $io->success("Analyse des fiches marchés terminé !");
        // Affichage de la liste
        $this->displayTable($output,$hotlines) ;
        
        $output->writeln([
            "",
            "<info>{$nbrDemandes} fiche(s) identifiée(s)!</>",
        ]);

        // Retourné l'etat de la commande
        return Command::SUCCESS;
    }
    
    /**
     * 
     * @param OutputInterface $output
     * @param array $data
     * @return void
     */
    public function displayTable(OutputInterface $output,array $data = []): void {
        (new Table($output))
                ->setHeaders(['Identifiant', 'Titre', 'Date de début', 'Date d\'expiration'])
                ->setRows($data)
                ->render() ;
    }

}

<?php

namespace App\Repository\Hotline;

use App\Exceptions\InvalidParameterException;
use Core\Util\Date ;
use App\Models\Hotline;
use App\Models\HotlineFil;
use App\Repository\AbstractRepository;
use App\Security\User;
use App\Validation\Validator;
use Core\Mailer\Builder as Mailer ;
use App\Exceptions\EmailException;
use App\Models\HotlineGroup;
use Exception;

class HotlineRepository extends AbstractRepository
{
    public function getList($options,User $user)
    {   
        $validator = new Validator($options); // validation
        $errors = $validator->validate([
            'email' => ['required'],
        ]);
        
        if ($errors) :
            $options['email'] = $user->getEmail() ;
        endif;

        $hotline = new Hotline();
        $options['date'] = date('Y-m-d');
       
        return $this->getAnswers($hotline->getList($options), $user, $options) ;
    }


    public function getHotline($options)
    {
        $validator = new Validator($options);
        $errors = $validator->validate([
            'id' => ['required'],
        ]);

        $id = $options['id'];
        if ($errors) {
            throw (new InvalidParameterException())->setErrors($errors);
        }

        $question = (new Hotline())->getHotline($id);
        if ($errors) {
            throw (new Exception('hotline.discussion_not_found'));
        }

        $timeline = (new HotlineFil())->getTimeLineByQuestion($id);
       
        $answer['data'] = array(
            'info' => $question,
            'timeline' => $timeline,
            'isRequester' => false,
            'isHotliner' => false,
            'status' => true
        );

        $isHotliner = false;
        if (isset($options['sha']) && !empty($options['sha'])) {
            $sha = $options['sha'];
            $infos = (new Hotline())->getInfos($id, $sha);
            $isHotliner = $infos['status'];
            if ($isHotliner === false) {
                $answer['data']['isRequester'] = true;
            } else if ($isHotliner === true) {
                $answer['data']['isHotliner'] = true;
            }
        }
        // Nombre d'éléments dans la discussion :
        $c = count($answer['data']['timeline']);
        if (!$isHotliner) {
            // Si on n'est pas hotliner, on supprime alors les noms des acheteurs.
            for ($i = 0; $i < $c; $i++) {
                $row = $answer['data']['timeline'][$i];
                $answer['data']['timeline'][$i]['message'] = $row['message'];
            }
        }
        // On met en forme les messages relatifs aux pièces jointes :
        for ($i = 0; $i < $c; $i++) {
            $row = $answer['data']['timeline'][$i];
            if ($row['nature'] === 'U') {
                $p = strpos($row['message'], ':');
                $answer['data']['timeline'][$i]['message'] = substr($row['message'], $p + 1);
                $answer['data']['timeline'][$i]['file'] = substr($row['message'], 0, $p);
            }
            $answer['data']['timeline'][$i]['message'] = str_replace("da-dag-achatpedia@edf.fr", "*****@edf.fr", $answer['data']['timeline'][$i]['message']) ;
        }

        return $answer;
    }
    
    /**
     * Parse Data inside answers
     * @param array $res
     * @param User $user
     * @param array $options
     * @return array|null
     */
    public function getAnswers(array $res,User $user,array $options):?array {
        $answer = ['status' => true,'message' => '','data' => ['new' => [],'progress' => [],'end' => []]];
        foreach ($res as $row) {
            if (in_array($row['natures'], array('O', 'OF', 'FO'))) {
                $key = 'new';
            } else if (strpos($row['natures'], 'C') !== false || $row['closed_at']!=null) {
                $key = 'end';
            } else {
                $key = 'progress';
            }
            $answer['data'][$key][] = array(
                'id' => $row['discussion'],
                'opened_at' => $row['opened_at'],
                'closed_at' => $row['closed_at'],
                'question' => $row['question'],
                'sha' => ($user->getEmail() == $options['email']) ? ($row['is_hotline'] ? $row['sha_hotline'] : $row['sha_demandeur']) : '',
            );
        }
        return $answer ;
    }
    
    /**
     * 
     * @param int $questionId
     * @return bool|null
     */
    public function getNotAnswered(int $questionId): ?bool {
       $result =  (new HotlineFil())->isCommented($questionId) ;
       return (bool)(count($result)>0) ;
    }
    
    /**
     * 
     * @return array
     */
    public function listAll(){
       return (new HotlineFil())->getAll() ;
    }
    
    /**
     * 
     * @return array
     */
    public function updateNotif(){
       return (new HotlineFil())->updateNotif() ;
    }


    public function getHotlines(){
        $hotlines =  (new Hotline())->getHotlines();
        $groups = (new HotlineGroup())->getList();
        $families = [];
        foreach($groups as $p){
            $families[$p['code']] = $p['label'];
        }
        $status = array_flip(Hotline::$status);

        foreach($hotlines as &$valeur){
            
             if (in_array($valeur['nature'], array('O', 'OF', 'FO'))) {
                    $valeur["status"] = 'nouvelle - en attente de traitement';
                    $valeur["closed_type"]  = '';
            } else if (strpos($valeur['nature'], 'C') !== false) {
                $valeur["status"] = 'fermée';
                if (isset($status[$valeur["closed_type"]])) {
                    $valeur["closed_type"] = $status[$valeur["closed_type"]];
                }else{
                    $valeur["closed_type"] = "GESTIONNAIRE";
                }
            } else {
                $valeur["status"] = 'ouverte - en cours de traitement';
                $valeur["closed_type"]  = '';
            }

            if (isset($families[$valeur["groupe"]])) {
                $valeur["groupe"] = $families[$valeur["groupe"]];
            }
           

            unset($valeur["row"]) ;
            unset($valeur["nature"]) ;
            unset($valeur["is_hotline"]) ;
            unset($valeur["closed_at"]) ;
            
        }
       return  $hotlines;
    }
    
        /**
     * 
     * @param int $endDays , default = 15
     * @return array|null
     */
    public function cloturerDemandes( int $endDays = 30): ?array {
        $hotlineModel = (new Hotline()) ;
        $hotlineFilModel = (new HotlineFil()) ;
        $hotlines = $hotlineModel->getExpiredDiscussion($endDays); 
        $stat = [];
        foreach ($hotlines as $row) {
            $stat = array();
            $stat['discussion'] = $row['discussion'];
            // On prépare la liste des emails :
            $tmp = explode(',', $row['emails']);
            $emails = array();
            foreach ($tmp as $e) {
                if (!empty($e)) {
                    if (!in_array($e, $emails)) {
                        $emails[] = $e;
                    }
                }
            }
            $stat['emails'] = $emails;
            $note= 0 ;
            $now = date('Y-m-d');
            // On ferme automatiquement la discussion :
            $id = $row['discussion'];
            $options = [
                "discussion"=> $id,
                "nature"=> 'C',
                "is_hotline"=> true ,
                "email"=>'' ,
                "message"=> "Fermée automatiquement",
                ] ;
            $hotlineFilModel->insert($options) ;
            (new Hotline())->close($id, $note,Hotline::$status["AUTOMATIQUE"]);
            
            if (!empty($emails)) {
                foreach ($emails as $email) {
                    if(strtolower($email) != 'da-dag-achatpedia@edf.fr'){
                        $this->sendMail($email, "Achatpédia - Fermeture automatique de la discussion $id", "mail/hotline_question_closed_auto.html.twig", [
                        'id' => $id,
                        'max_days' => $endDays,
                    ]);
                    }
                }
            }
            $this->sendMail('da-dag-achatpedia@edf.fr', "Achatpédia - Fermeture automatique de la discussion $id", "mail/hotline_question_closed_auto_eha.html.twig", [
                'id' => $id,
                'max_days' => $endDays,
            ]);
        }
        return $hotlines;
    }
    
     public function sendMail($recepients, $subject, $template, $params)
    {
        try{
        $params['config_domain'] = getenv('CONFIG_DOMAINE');
        $mailer =  (new Mailer())
            ->setSubject($subject)
            ->setRecepients($recepients)
            ->html($template, $params);
        $mailer->send(); // Envoyer l'email 
    } catch (Exception $e) {
        throw new EmailException();
    }
    }
    
    public function sendMailff(string $text):void{
        // On transmet un mail d'information :
	if (!empty($emails)) {
		$subject = 'Achatpédia - Fermeture automatique de la discussion ' . $id;
		$body = '<p>Cela fait maintenant au moins '.$maxDays.' jours, qu\'aucun demandeur n\'a répondu au dernier message de la hotline.</p><p>En conséquence de quoi, la discussion '.$id.' est fermée automatiquement.</p>';
		// préparation du mail
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8'. "\r\n";
		$headers .= 'From: '. $config_noreply_email . "\r\n";
		$headers .= 'Reply-To: ' . $config_noreply_email . "\r\n";
		$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
		$html = '<html><head>Achatpédia</head><body>' . $body . '</body></html>';
		if (mail(implode(',', $emails), $subject, $html, $headers)) {
			$stat['is_email'] = true;
		} else {
			$stat['is_email'] = false;
		}
	}
	 

    }
}
    
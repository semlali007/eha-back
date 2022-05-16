<?php

namespace App\Repository\Hotline;

use App\Exceptions\InvalidParameterException;
use App\Models\Hotline;
use App\Models\HotlineFil;
use App\Repository\AbstractRepository;
use App\Security\User;
use App\Validation\Validator;
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
        return  (new Hotline())->getHotlines();
    }
}

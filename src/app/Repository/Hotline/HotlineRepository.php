<?php

namespace App\Repository\Hotline;

use App\Exceptions\InvalidParameterException;
use App\Models\Hotline;
use App\Models\HotlineFil;
use App\Repository\AbstractRepository;
use App\Services\UserService;
use App\Validation\Validator;
use Exception;

class HotlineRepository extends AbstractRepository
{
    public function getList($options)
    {
        $user  = UserService::getUser();
        $validator = new Validator($options);
        $errors = $validator->validate([
            'email' => ['required'],
        ]);

        if ($errors) {
            throw (new InvalidParameterException())->setErrors($errors);
        }

        $hotline = new Hotline();
        $dateMin = date('Y-m-d');
        $dateMin = explode('-', $dateMin);
        $options['date'] = ($dateMin[0] - 1) . '-' . $dateMin[1] . '-' . $dateMin[2];
        $res = $hotline->getList($options);
        $answer['status'] = true;
        $answer['message'] = '';
        $answer['data'] = [
            'new' => [],
            'progress' => [],
            'end' => []
        ];
        foreach ($res as $row) {
            if (in_array($row['natures'], array('O', 'OF', 'FO'))) {
                $key = 'new';
            } else if (strpos($row['natures'], 'C') !== false) {
                $key = 'end';
            } else {
                $key = 'progress';
            }
            $answer['data'][$key][] = array(
                'id' => $row['discussion'],
                'opened_at' => $row['opened_at'],
                'closed_at' => $row['closed_at'],
                'question' => $row['question'],
                'sha' => ($user['email'] == $options['email']) ? ($row['is_hotline'] ? $row['sha_hotline'] : $row['sha_demandeur']) : '',
            );
        }

        return $answer;
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
                if ($row['is_hotline']) {
                    $p1 = strpos($row['message'], '@');
                    if ($p1 !== false) {
                        // On masque le nom dans l'email :
                        $p0 = strrpos(substr($row['message'], 0, $p1), '.');
                        if ($p0 !== false) {
                            $answer['data']['timeline'][$i]['message'] =
                                substr($row['message'], 0, $p0 + 1)
                                . '******'
                                . substr($row['message'], $p1);
                        } else {
                            $p0 = $p1;
                            while ($p0 > 0) {
                                $p0 -= 1;
                                if (substr($row['message'], $p0, 1) === ' ') {
                                    break;
                                }
                            }
                            $answer['data']['timeline'][$i]['message'] =
                                substr($row['message'], 0, $p0 + 1)
                                . '******'
                                . substr($row['message'], $p1);
                        }
                    }
                }
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
}

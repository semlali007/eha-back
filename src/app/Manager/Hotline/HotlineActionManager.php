<?php

namespace App\Manager\Hotline;

use App\Exceptions\EmailException;
use App\Exceptions\InvalidParameterException;
use App\Manager\AbstractManager;
use App\Models\Hotline;
use App\Models\HotlineEmail;
use App\Models\HotlineFil;
use App\Models\HotlineFile;
use App\Models\HotlineTheme;
use App\Services\EmailService;
use App\Services\StaticTextService;
use App\Services\UserService;
use App\Validation\Validator;
use Exception;

class HotlineActionManager extends AbstractManager
{

    public function upload($options, $file)
    {

        $id = $options['id'];
        $sha = $options['sha'];
        $name = $options['name'];
        $infos = (new Hotline())->getInfos($id, $sha);
        $isHotline = $infos['status'];
        if ($infos['status'] === null) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['02'] . '</p>';
            return $answer;
        }

        if ($infos['status'] === null) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['10'] . '</p>';
            return $answer;
        }
        if ($file['error'] !== 0) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['31'] . '</p>';
            return $answer;
        }

        if ($file['size'] > 1000000) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['32'] . '</p>';
            return $answer;
        }
        $e = explode('.', $file['name']);
        $e = end($e);
        $filename =  basename($id . '_' . date('Ymdhis') . '.' . $e);

        if (!move_uploaded_file($file['tmp_name'], getenv("CONFIG_UPLOAD_PATH") . $filename)) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['33'] . '</p>';
            return $answer;
        }

        $msg = $filename . ':' . $name;
        $hotlineFilTime = new HotlineFil();
        $lastId = $hotlineFilTime->insert([
            'discussion' => $id,
            'nature' => 'U',
            'is_hotline' => $isHotline ? true : false,
            'email' => '',
            'message' => $msg,
        ]);

        (new HotlineFile())->insert([
            'discussion' => $id,
            'file_code' => $filename,
            'file_name' => $name
        ]);

        $answer['status'] = true;
        $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['34'] . '</p>';
        $email = (new Hotline())->getCounterparts(['id' => $id, 'is_hotline' => $isHotline]);

        if (empty($email)) {
            return $answer;
        }

        try {
            $sha = ($isHotline) ? $infos['sha_demandeur'] : $infos['sha_hotline'];

            EmailService::sendEmail('hotline_question_upload_file', $email, [
                'id' => $id,
                'sha_hotline' =>  $sha,
                'config_domain' => CONFIG_DOMAIN,
            ]);
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['34'] . '</p>';
        } catch (EmailException $ex) {
            $answer['status'] = false;
        }

        return $answer;
    }
}

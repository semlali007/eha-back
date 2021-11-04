<?php

namespace App\Manager\Hotline;

use App\Exceptions\EmailException;
use App\Exceptions\InvalidParameterException;
use App\Manager\AbstractManager;
use App\Models\Hotline;
use App\Models\HotlineEmail;
use App\Models\HotlineFil;
use App\Models\HotlineTheme;
use App\Services\EmailService;
use App\Services\StaticTextService;
use App\Services\UserService;
use App\Validation\Validator;
use Exception;

class HotlineManager extends AbstractManager
{
    public function create($options)
    {

        $user = UserService::getUser();
        $entity = substr($user['direction'], 0, 45);
        $validator = new Validator($options);
        $errors = $validator->validate([
            'post' => ['required'],
            'entity' => ['required'],
            'theme' => ['required'],
            'category' => ['required']
        ]);

        if ($errors) {
            throw (new InvalidParameterException())->setErrors($errors);
        }
        $email = $user['email'];
        $category = $options['category'];
        $theme = $options['theme'];
        $sha_demandeur = crypt($email . bin2hex(random_bytes(10)) . 'h');
        $sha_hotline   = crypt($email . bin2hex(random_bytes(10)) . 'd');
        $post = $options['post'];
        $emailTo = null;
        $hotlineEmail = new HotlineEmail();
        $hotliner = $hotlineEmail->getHotlineInformations($category);
        if (!is_array($hotliner) || empty($hotliner) || !isset($hotliner['email'])) {
            throw new Exception('hotline.no_hotliner_found');
        }

        $emailTo = $hotliner['email'];
        $categoryLabel = $hotliner['category'];
        if (!empty($emailTo)) {
            $hotline = new Hotline();
            $id = $hotline->insertRequest([
                'email' => $email,
                'entity' => $entity,
                'category' => $category,
                'post' => substr($post, 0, 450),
                'sha_demandeur' => $sha_demandeur,
                'sha_hotline' => $sha_hotline,
                'theme' => $theme
            ]);

            $hotlineFilTime = new HotlineFil();
            $hotlineFilTime->insert([
                'discussion' => $id,
                'nature' => 'O',
                'is_hotline' => false,
                'email' => $email,
                'message' => substr($post, 0, 4900),
            ]);

            $hotlineFilTime->insert([
                'discussion' => $id,
                'nature' => 'F',
                'is_hotline' => true,
                'email' => $emailTo,
                'message' => 'Question affectée automatiquement à ' . $emailTo,
            ]);

            EmailService::sendEmail('hotline_question_creation_to_user', $email, [
                'id' => $id,
                'sha_demandeur' => $sha_demandeur,
                'message' => StaticTextService::$_HOTLINE_TEXT['05'],
                'config_domain' => CONFIG_DOMAIN
            ]);

            $themeData = (new HotlineTheme())->get($theme);
            if (isset($themeData['label'])) {
                EmailService::sendEmail('hotline_question_creation_to_hotliner', $emailTo, [
                    'id' => $id,
                    'sha_hotline' => $sha_hotline,
                    'message' => StaticTextService::$_HOTLINE_TEXT['06'],
                    'config_domain' => CONFIG_DOMAIN,
                    'theme' => $themeData['label'],
                    'category_label' => (empty($categoryLabel)) ? '' : ' - ' . $categoryLabel
                ]);
            }
        }
    }


    public function forward($options)
    {
        $answer = array(
            'logged' => true,
            'status'  =>  false,
            'message' => '',
            'data' => null,
        );

        $validator = new Validator($options);
        $errors = $validator->validate([
            'id' => ['required'],
            'sha' => ['required'],
            'email' => ['required'],
        ]);

        if ($errors) {
            throw (new InvalidParameterException())->setErrors($errors);
        }

        $id = $options['id'];
        $sha = $options['sha'];
        $email = $options['email'];
        $infos = (new Hotline())->getInfos($id, $sha);
        $groupe = $infos['groupe'];
        $isHotline = $infos['status'];
        if ($isHotline === null) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['10'] . '</p>';
            return $answer;
        }

        $hotlineFilTime = new HotlineFil();
        $lastId = $hotlineFilTime->insert([
            'discussion' => $id,
            'nature' => 'F',
            'is_hotline' => $isHotline ? true : false,
            'email' => $email,
            'message' => ($isHotline) ? 'Question transférée à ' . $email : 'Demande partagée avec ' . $email,
        ]);

        if (!is_numeric($lastId) || $lastId <= 0) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['11'] . '</p>';
            return $answer;
        }

        $answer['status'] = true;
        try {
            if ($isHotline) {
                EmailService::sendEmail('hotline_question_forword_to_hotliner', $email, [
                    'id' => $id,
                    'sha_hotline' => $sha,
                    'message' => StaticTextService::$_HOTLINE_TEXT['12'],
                    'config_domain' => CONFIG_DOMAIN,
                    'group' => (empty($groupe) ? '' : ' pour ' . $groupe),
                ]);
            } else {
                EmailService::sendEmail('hotline_question_forword_to_direction', $email, [
                    'id' => $id,
                    'sha_hotline' => $sha,
                    'message' => StaticTextService::$_HOTLINE_TEXT['13'],
                    'config_domain' => CONFIG_DOMAIN,
                ]);
            }
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['15'] . '</p>';
        } catch (EmailException $ex) {
            $answer['status'] = false;
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['14'] . '</p>';
        }
        return $answer;
    }


    public function post($options)
    {
        $validator = new Validator($options);
        $errors = $validator->validate([
            'id' => ['required'],
            'sha' => ['required'],
            'post' => ['post'],
        ]);

        if ($errors) {
            throw (new InvalidParameterException())->setErrors($errors);
        }

        $answer = array(
            'logged' => true,
            'status'  =>  false,
            'message' => '',
            'data' => null,
        );
        $id = $options['id'];
        $sha = $options['sha'];
        $post = $options['post'];
        $infos = (new Hotline())->getInfos($id, $sha);
        if ($infos['status'] === null) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['02'] . '</p>';
            return $answer;
        }

        $groupe = $infos['groupe'];
        $isHotline = $infos['status'];
        $hotlineFilTime = new HotlineFil();
        $lastId = $hotlineFilTime->insert([
            'discussion' => $id,
            'nature' => 'P',
            'is_hotline' => $isHotline ? true : false,
            'email' => '',
            'message' => $post,
        ]);

        if (!is_numeric($lastId) || $lastId <= 0) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['17'] . '</p>';
            return $answer;
        }

        $answer['status'] = true;
        $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['25'] . '</p>';
        $email = (new Hotline())->getCounterparts(['id' => $id, 'is_hotline' => $isHotline]);

        if (empty($email)) {
            return $answer;
        }

        try {
            if ($isHotline) {
                EmailService::sendEmail('hotline_question_post_new_hotline', $email, [
                    'id' => $id,
                    'sha_hotline' => $infos['sha_demandeur'],
                    'message' => StaticTextService::$_HOTLINE_TEXT['18'],
                    'config_domain' => CONFIG_DOMAIN,
                ]);
            } else {
                EmailService::sendEmail('hotline_question_post_new_user', $email, [
                    'id' => $id,
                    'sha_hotline' => $infos['sha_hotline'],
                    'message' => StaticTextService::$_HOTLINE_TEXT['19'],
                    'config_domain' => CONFIG_DOMAIN,
                ]);
            }
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['20'] . '</p>';
        } catch (EmailException $ex) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['21'] . '</p>';
            $answer['message'] .=  '<p><a href="https://' . CONFIG_DOMAIN . '/app/hotline/get/' . $id . '/' . $sha . '">Achatpédia - Discussion ' . $id . '</a></p>';
        }

        return $answer;
    }
}

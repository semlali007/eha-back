<?php

namespace App\Manager\Hotline;

use App\Exceptions\EmailException;
use App\Exceptions\InvalidParameterException;
use App\Manager\AbstractManager;
use App\Models\Hotline;
use App\Models\HotlineEmail;
use App\Models\HotlineFil;
use App\Models\HotlineTheme;
use App\Services\StaticTextService;
use App\Validation\Validator;
use Exception;
use Core\Mailer\Builder as Mailer ;
use App\Security\User ;

class HotlineManager extends AbstractManager
{
    public function create($options,User $user)
    {  
        $entity = substr($user->getDirection(), 0, 45);
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
        $email = $user->getEmail();

        if($email == getenv('DEFAULT_SEND_EMAIL') && isset($options['email']) && !empty($options['email'])){
            $email = $options['email'];
        }

        if($entity == 'DEFAULT' && isset($options['entity'])){
            $entity = substr($options['entity'], 0, 45);
        }

        $category = $options['category'];
        $theme = $options['theme'];
        $sha_demandeur = bin2hex(random_bytes(2)). 'h';
        $sha_hotline   = bin2hex(random_bytes(2)). 'd';
        $post = $options['post'];
       
        $hotlineEmail = new HotlineEmail();
        $hotliner = $hotlineEmail->getHotlineInformations($category);
        if (!is_array($hotliner) || empty($hotliner) || !isset($hotliner['email'])) {
            throw new Exception('hotline.no_hotliner_found');
        }
        
        if ((array_key_exists('email', $hotliner))) {
            $emailTo = $hotliner['email'];
            $categoryLabel = $hotliner['category'];
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

            $maskedEmailTo = ($options['user'] instanceof User) && ($options['user'])->hasRole(['EHA_ADMIN', 'EHA_GESTIONNAIRE']) ? $emailTo : '*****@edf.fr';
            $hotlineFilTime->insert([
                'discussion' => $id,
                'nature' => 'F',
                'is_hotline' => true,
                'email' => $maskedEmailTo,
                'message' => 'Question affectée automatiquement à ' . $maskedEmailTo,
            ]);

            $this->sendMail($email, "Achatpédia - Q$id - Création", "mail/hotline_question_creation_to_user.html.twig", [
                'id' => $id,
                'sha' => $sha_demandeur,
            ], "Création");

            $themeData = (new HotlineTheme())->get($theme);
            if (isset($themeData['label'])) {
                $label = (empty($categoryLabel)) ? '' : ' - ' . $categoryLabel;
                $theme = $themeData['label'];
                $subject = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|Grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities("Achatpédia - Q{$id} - Création {$theme} {$label}", ENT_QUOTES, 'UTF-8'));
               
                $this->sendMail($emailTo, $subject, "mail/hotline_question_creation_to_hotliner.html.twig", [
                    'id' => $id,
                    'sha' => $sha_hotline,
                ], "Création");
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
        $emailcc = $options['emailcc'];
        $infos = (new Hotline())->getInfos($id, $sha);
        $groupe = $infos['groupe'];
        $isHotline = $infos['status'];
        if ($isHotline === null) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['10'] . '</p>';
            return $answer;
        }

        $hotlineFilTime = new HotlineFil();
        $miseencopie = (!empty($emailcc))?" \n Demande mise en copie avec ".$emailcc:"";   
        $lastId = $hotlineFilTime->insert([
            'discussion' => $id,
            'nature' => 'F',
            'is_hotline' => $isHotline ? true : false,
            'email' => $email,
            'message' => ($isHotline) ? 'Question transférée à ' . $email : 'Demande partagée avec ' . $email.$miseencopie,
        ]);

        if (!is_numeric($lastId) || $lastId <= 0) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['11'] . '</p>';
            return $answer;
        }

        $answer['status'] = true;
        try {
            if ($isHotline) {
                $subject = 'Achatpédia - Q' . $id . ' - Message';
                $subject .= (empty($groupe) ? '' : ' pour ' . $groupe);
                $this->sendMail($email, $subject, "mail/hotline_question_forword_to_hotliner.html.twig", [
                    'id' => $id,
                    'sha' => $sha,
                ], "Message");
            } else {
                $this->sendMail($email, "Achatpédia - Q$id - Pour info", "mail/hotline_question_forword_to_user.html.twig", [
                    'id' => $id,
                    'sha' => $sha,
                ], "Pour info");
            }
            
            if(!empty($emailcc)) {
                $this->sendMail($emailcc, "Achatpédia - Q$id - Pour info", "mail/hotline_question_forword_cc.html.twig", [
                    'id' => $id,
                    'sha' => $sha,
                    'email' => $email,
                ], "Pour info");
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
        $emails = (new Hotline())->getCounterparts(['id' => $id, 'is_hotline' => $isHotline]);
        if (empty($emails)) {
            return $answer;
        }
        try {
            if ($isHotline) {
                $this->sendMail($emails, "Achatpédia - Q$id - Nouveau message de la hotline", "mail/hotline_new_question_post_from_hotline.html.twig", [
                    'id' => $id,
                    'sha' => $infos['sha_demandeur'],
                ], "Nouveau message de la hotline");
            } else {
                $this->sendMail($emails, "Achatpédia - Q$id - Nouveau message du demandeur", "mail/hotline_new_question_post_from_user.html.twig", [
                    'id' => $id,
                    'sha' => $infos['sha_hotline'],
                ], "Nouveau message de la hotline");
            }
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['20'] . '</p>';
        } catch (EmailException $ex) {
            $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['14'] . '</p>';
        }
        return $answer;
    }

    public function sendMail($recepients, $subject, $template, $params, $action)
    {
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
        // Ajoute l'env au sujet de l'e-mail
        $subjectWithEnvironment = "Achatpédia - Q" . $prefix . $params['id'] . '-' . $envLabel . '-' . $action;

        try {
            $params['config_domain'] = getenv('CONFIG_DOMAINE');

            $mailer =  (new Mailer())
                ->setSubject($subjectWithEnvironment)
                ->setRecepients($recepients)
                ->html($template, $params);
            $mailer->send(); // Envoyer l'email
        } catch (Exception $e) {
            error_log("Error sending email: " . $e->getMessage());
            throw new EmailException();
        }
    }

}
<?php

namespace App\Services;

use App\Exceptions\EmailException;

class EmailService
{
    const TEMPLATES = [
        'hotline_question_creation_to_user' => [
            'subject' => 'Achatpédia - Q{{ id }} - Création',
            'content' => '<p>{{ message }}</p><p><a href="https://{{ config_domain }}/app/hotline/get/{{ id }}/{{ sha_demandeur }}">Achatpédia - Discussion n°{{ id }}</a></p>'
        ],
        'hotline_question_creation_to_hotliner' => [
            'subject' => 'Achatpédia - Q{{ id }} - Création - {{ theme }} {{ category_label }}',
            'content' => '<p>{{ message }}</p><p><a href="https://{{ config_domain }}/app/hotline/get/{{ id }}/{{ sha_hotline }}">Achatpédia - Discussion n°{{ id }}</a></p>'
        ],
        'hotline_question_forword_to_hotliner' => [
            'subject' => 'Achatpédia - Q{{ id }} - Message {{ group }}',
            'content' => '<p>{{ message }}</p><p><a href="https://{{ config_domain }}/app/hotline/get/{{ id }}/{{ sha_hotline }}">Achatpédia - Discussion n°{{ id }}</a></p>'
        ],
        'hotline_question_forword_to_direction' => [
            'subject' => 'Achatpédia - Q{{ id }} - Pour info',
            'content' => '<p>{{ message }}</p><p><a href="https://{{ config_domain }}/app/hotline/get/{{ id }}/{{ sha_hotline }}">Achatpédia - Discussion n°{{ id }}</a></p>'
        ],
        'hotline_question_post_new_hotline' => [
            'subject' => 'Achatpédia - Q{{ id }} - Nouveau message de la hotline',
            'content' => '<p>{{ message }}</p><p><a href="https://{{ config_domain }}/app/hotline/get/{{ id }}/{{ sha_hotline }}">Achatpédia - Discussion n°{{ id }}</a></p>'
        ],
        'hotline_question_post_new_user' => [
            'subject' => 'Achatpédia - Q{{ id }} - Nouveau message du demandeur',
            'content' => '<p>{{ message }}</p><p><a href="https://{{ config_domain }}/app/hotline/get/{{ id }}/{{ sha_hotline }}">Achatpédia - Discussion n°{{ id }}</a></p>'
        ],
        'hotline_question_upload_file' => [
            'subject' => 'Achatpédia - Q{{ id }} - Pièce jointe ajoutée',
            'content' => '<a href="https://{{ config_domain }}/app/hotline/get/{{ id }}/{{ sha_hotline }}">Achatpédia - Discussion n°{{ id }}</a></p>'
        ]
    ];

    public static function sendEmail($template = '', $to = '', $data = array())
    {
        if ($to === '') {
            throw new EmailException('Erreur d\'envoi d\'un e-mail : destinataire vide.');
        }

        if ($template === '') {
            throw new EmailException('Erreur d\'envoi d\'un e-mail : template vide.');
        }

        if (!array_key_exists($template, self::TEMPLATES)) {
            throw new EmailException('Erreur d\'envoi d\'un e-mail : template inexistant.');
        }

        $templateData = self::TEMPLATES[$template];
        $mailSubjectTemplate = $templateData['subject'];
        $mailContentTemplate = $templateData['content'];
        $subject = self::transformVarsToValue($mailSubjectTemplate, $data);
        $mailContent = self::transformVarsToValue($mailContentTemplate, $data);
        $content = self::generateHtmlContent(['body' => $mailContent]);
        $headers = self::generateHeaders(['config_noreply_email' => CONFIG_NOREPLY_EMAIL]);
        if (!@mail($to, $subject, $content, $headers)) {
            throw new EmailException('Erreur durant l\'envoi de l\'e-mail.');
        }
    }

    public static function transformVarsToValue($template, $vars)
    {
        $output = $template;
        foreach ($vars as $key => $value) {
            $tagToReplace = "{{ $key }}";
            $output = str_replace($tagToReplace, $value, $output);
        }

        return $output;
    }


    public static function generateHtmlContent($options)
    {
        $body = $options['body'];
        $html = '<html><head>Achatpédia</head><body>' . $body . '</body></html>';
        return $html;
    }

    public static function generateHeaders($options)
    {
        $email = $options['config_noreply_email'];
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $headers .= 'From: ' . $email . "\r\n";
        $headers .= 'Reply-To: ' . $email . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
        return $headers;
    }
}

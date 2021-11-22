<?php

namespace Core\Mailer;

use Core\Mailer\{
    Mailer,
    SMTP
} ;

/**
 * Description of Builder
 *
 * @author HGDDEDBN
 */
class Builder {

    /**
     * 
     * @var string
     */
    public Mailer $mail;

    /**
     * Constructeur
     * @param bool $enbaleException
     */
    public function __construct(bool $enbaleException = true) {
        $this->mail = new Mailer($enbaleException); // Mailer
        if (filter_var(getenv("ENABLE_CUSTOM_CONF"), FILTER_VALIDATE_BOOLEAN)) {
            $this->enableCustomSettings();
        }
    }

    /**
     * Cette methode créée pour modifier les configs se smtp
     * Charger les configurations SMTP
     * @return void
     */
    public function enableCustomSettings(): void {
        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
        $this->mail->isSMTP();  //Send using SMTP
        $this->mail->Host = getenv("SMTP_HOST"); //Set the SMTP server to send through
        $this->mail->SMTPAuth = getenv("SMTP_AUTH"); //Enable SMTP authentication
        $this->mail->Username = getenv("SMTP_USERNAME"); //SMTP username
        $this->mail->Password = filter_var(getenv("SMTP_PWD"), FILTER_VALIDATE_BOOLEAN);  //SMTP password
        $this->mail->SMTPSecure = Mailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
        $this->mail->Port = getenv("SMTP_PORT"); //SMTP PORT
    }

    /**
     * 
     * @param string $subject
     * @return void
     */
    public function setSubject(string $subject = ""): self {
        $this->mail->Subject = $subject;
        return $this;
    }

    /**
     * 
     * @param string $emailFrom
     * @param string $titleFrom
     * @return void
     */
    public function setFrom(string $emailFrom, string $titleFrom): self {
        $this->mail->setFrom($emailFrom, $titleFrom);
        return $this;
    }

    /**
     * Exemple: ["hamdi-externe.garfi@edf.fr"=>"hamdi GARFI"]
     * @param array $recepients
     * @return void
     */
    public function setRecepients(array $recepients): self {
        foreach ($recepients as $email => $name) {
            $this->mail->addAddress($email, $name);     //Add a recipient
        }
        return $this;
    }

    /**
     * 
     * @param string $emailReplyTo
     * @param string $titleReplyTo
     * @return void
     */
    public function addReplayTo(string $emailReplyTo, string $titleReplyTo): self {
        $this->mail->addReplyTo($emailReplyTo, $titleReplyTo);
        return $this;
    }

    /**
     * 
     * @param string $email
     * @return void
     */
    public function addCC(string $email): self {
        $this->mail->addCC($email);
        return $this;
    }

    /**
     * 
     * @param string $email
     * @return void
     */
    public function addBCC(string $email): self {
        $this->mail->addBCC($email);
        return $this;
    }

    /**
     * 
     * @param string $path
     * @param string $title
     * @return void
     */
    public function addAttachment(string $path, string $title = ""): self {
        $this->mail->addAttachment($path, $title); //Add attachments
        return $this;
    }

    /**
     * 
     * @param string $pathFileHtml
     * @param array $params
     * @return void
     */
    public function renderHtml(string $pathFileHtml, array $params = []): self {
        $html = $this->render($pathFileHtml, $params);
        $this->mail->msgHTML($html, __DIR__);
        return $this;
    }

    /**
     * 
     * @param string $html
     * @param string $altBody
     */
    public function setBodyHtmlContent(string $html, string $altBody = ""): self {
        $this->mail->Body = $html;
        $this->mail->AltBody = $altBody;
        return $this;
    }

    /**
     * 
     * @param string $pathFileHtml
     * @return string
     */
    public function render(string $pathFileHtml, array $params, string $folder = "templates"): string {
        $output = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . "" . $folder . DIRECTORY_SEPARATOR . $pathFileHtml);
        foreach ($params as $key => $value) {
            $tagToReplace = "##$key##";
            $output = str_replace($tagToReplace, $value, $output);
        }
        return $output;
    }

    /**
     * Envoi de l'email
     */
    public function send() {
        $this->mail->send();
    }

}

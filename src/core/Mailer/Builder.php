<?php

namespace Core\Mailer;

use Twig\{Loader\FilesystemLoader, Environment};
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
    
    const PATH_TEMPLATING = "templates/" ;

    /**
     * 
     * @var string
     */
    public Mailer $mail;
    
      /**
     * 
     * @var string
     */
    public $twig;

    /**
     * Constructeur
     * @param bool $enbaleException
     */
    public function __construct(bool $enbaleException = true) {
        $templateBasePath = dirname(__DIR__). '/../' . self::PATH_TEMPLATING ;
        $this->twig = new Environment(new FilesystemLoader($templateBasePath)); // Templating
        $this->mail = new Mailer($enbaleException); // Mailer
        $this->mail->ContentType = "Content-type: text/plain; charset=UTF-8" ;
        $this->mail->setLanguage('fr');
        $this->setFrom(getenv("EMAIL_NO_REPLAY"), getenv("EMAIL_NO_REPLY_NAME")) ;
        // Charger les prametres personnalisés
        if (filter_var(getenv("ENABLE_CUSTOM_CONF"), FILTER_VALIDATE_BOOLEAN)) :
            $this->enableCustomSettings();
        endif;
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
    public function setRecepients(mixed $recepients): self {
        if (is_array($recepients)) {
            foreach ($recepients as $email => $name) {
                $this->mail->addAddress($email, $name);     //Add a recipient
            }
        }elseif(is_string($recepients)){
             $this->mail->addAddress($recepients);
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
    public function html(string $pathFileHtml, array $params = []): self {
        $html = $this->render($pathFileHtml, $params);
        $this->mail->msgHTML($html, __DIR__);
        return $this;
    }
    /**
     * 
     * @param string $pathFileHtml
     * @param array $params
     * @return void
     */
    public function viewHtml(string $pathFileHtml, array $params = []):void{
         echo $this->render($pathFileHtml, $params);
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
    public function render(string $pathFileHtml, array $params): string {
         return $this->twig->render(
                $pathFileHtml,
               $params
        );
    }

    /**
     * Envoi de l'email
     */
    public function send() {
        $this->mail->send();
    }
    
    /**
     * 
     * @return mixed
     */
    public function getErrorInfo():mixed{
        return $this->mail->ErrorInfo ;
    }
}

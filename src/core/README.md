# EHA-BACK: Core module
Est Le core de l'appliction qui contient la partie technique de l'application

## Module: Mailer

### Usage:
Pour utiliser le mailer dans l'application, il faut faire  un appel au builder qui va construire toutes les instances d'emailing


** Exemple code  : **

```php
use Core\Mailer\Builder ; // appel de la classe Builder du module Mailer

 (new Builder()) // instancier le builder
                ->setSubject("test subject") // mettre le sujet
                ->setFrom("hamdi.garfi@gmail.com","hamdi GARFI") // emmetteur 
                ->setRecepients(["hamdi-externe.garfi@edf.fr" => "hamdi GARFI"]) // Recepteur
                ->renderHtml("mail/auto_notif_archivage.html",["key"=>" garfi hamdi"]) // appeler le template HTML
                ->send() // Envoyer l'email
                ;
```



Par defaut le mailer prend les parametres par defaut du serveur. 
Pour changer les parametres par defaut il faut les changer dans le fichier .env situé dans la racien du projet: 

```
### SMTP SERVER ###
ENABLE_CUSTOM_CONF=false
SMTP_HOST=NULL
SMTP_AUTH=true
SMTP_USERNAME=NULL
SMTP_PWD=secret
SMTP_PORT=465
```



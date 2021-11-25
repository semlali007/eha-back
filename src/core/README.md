# EHA-BACK: Core module
Est Le core de l'appliction qui contient la partie technique de l'application

## Module: Mailer

### Usage:
Pour utiliser le mailer dans l'application, il faut faire  un appel au builder qui va construire toutes les instances d'emailing


**Exemple code :**

```php
use Core\Mailer\Builder ; // appel de la classe Builder du module Mailer

 $key_binding = ["key"=>" garfi hamdi"]; //Va chercher une variable dans le template de type {{key}} (delimiter {{VARIABLE}})
 $pathTemplate = <PATH_TO_TEMPLATE>.html.twig ; // le fichier twig doit etre dans le dossier /templates 
 (new Builder()) // instancier le builder
                ->setSubject("test subject") // mettre le sujet
                ->setFrom("loremipsum@edf.fr","Lorem Ipsum") 
                ->setRecepients(["reception@edf.fr" => "hamdi GARFI"]) // Recepteur
                ->html($pathTemplate, $key_binding) // appeler le template HTML
                ->send() // Envoyer l'email
                ;
```

Exemple de template (path: RACINE_REPO/src/templates/mail):

```html
<html>
    <head>
        <title>TODO supply a title</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <div>Hello Email Archivage <strong>{{key}}</strong></div>
    </body>
</html>
```


* Par defaut le mailer prend les parametres par defaut du serveur.
* Pour changer les parametres il faut les modifier dans le fichier .env dans la racine du projet: 

```
### SMTP SERVER ###
ENABLE_CUSTOM_CONF=false
SMTP_HOST=NULL
SMTP_AUTH=true
SMTP_USERNAME=NULL
SMTP_PWD=secret
SMTP_PORT=465
```



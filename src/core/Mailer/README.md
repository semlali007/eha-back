# EHA-BACK 
Est la partie back de l'application Achatpédia codée en PHP basée sur le modèle MVC.

## Usage
Pour utiliser le mailer dans l'application, il faut faire  un appel au builder qui va construire toutes les instances d'emailing
par exemple dans un service:

Exemple code : 

```php
use Core\Mailer\Builder ;

 (new Builder())
                ->setSubject("test subject")
                ->setFrom("hamdi.garfi@gmail.com","hamdi GARFI")
                ->setRecepients(["hamdi-externe.garfi@edf.fr" => "hamdi GARFI"])
                ->renderHtml("mail/auto_notif_archivage.html",["key"=>" garfi hamdi"])
                ->send()
                ;
```



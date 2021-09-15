# EHA-BACK 
Cette application est la partie back de l'application Achatpédia codée en PHP basé sur le modèle MVC.


## Logiciels requis

-   PHP-FPM  **8.0.2**
-   Nginx  **1.9.2+** (Recommended **1.9.2**)

## Installation

### 1- Clonons le repository !
```
git clone https://si-devops-gitlab.edf.fr/eha-web-achat/eha-back.git ./appli/projects/PHPEHA
```
### 2- Changer le dossier
```
cd /appli/projects/PHPEHA/src
```

### 3- installer les dépendances (vendor)
```
composer install 
```


## Usage
Lancer l'application sur http://IP-ADDRESS:9000
(le port d’écoute est 9000)

## Configuration

Dans le fichier .env, remplissez les informations liées à la base de données que vous devez préalablement créer.
(ce fichier doit être automatiquement rempli par le pipeline jenkins )

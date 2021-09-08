<?php

namespace App\Controllers;

use App\Models\External;
use App\Models\Administrator;
use App\Services\EntityService;
use Database\DBConnection;

class AuthenticationController extends Controller
{
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
    }

    public function login()
    {
        session_start();
        $entity = EntityService::findEntity('/EDF/9651/9803/9708');
        $_SESSION['user'] = array(
            'nni' =>'WID03C5N',
            'email' =>'walid-externe.ibrahim@edf.fr',
            'direction' =>$entity[1],
            'entity_code' =>$entity[0],
            'full_name' => 'WALID IBRAHIM',//$userEntries[0]['cn'][0]
            'roles' => ['ADMIN', 'USER']
        );
        return json_encode(array(
            'logged' => true,
            'data' => array(
                'nni' => $_SESSION['user']['nni'],
                'email' => $_SESSION['user']['email'],
                'direction' => $_SESSION['user']['direction'],
                'entity_code' => $_SESSION['user']['entity_code'],
                'roles' => ['ADMIN', 'USER']
            )
        ));
        session_start();
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        if (!isset($data['nni']) || is_null($data['nni']) || empty($data['nni'])) {
            $this->response(
                [
                    'logged' => false,
                    'answer' => 'NNI est obligatoire.'
                ]
            );
        }

        $nni = $data['nni'];
        if (!isset($data['pwd']) || is_null($data['pwd']) || empty($data['pwd'])) {
            $this->response(
                [
                    'logged' => false,
                    'answer' => 'Mot de passe est obligatoire.'
                ]
            );
        }

        $pwd = $data['pwd'];
        unset($_SESSION['user']);
        $msg = 107;
        $ldapConn = ldap_connect(getenv('CONFIG_GARDIAN_HOST'), 636);
        if ($ldapConn === false) {
            // la connexion ne passe pas
            $msg = 101; // La connexion LDAP a échoué.
        } else {
            // version 3 de LDAP
            ldap_set_option($ldapConn, getenv('LDAP_OPT_PROTOCOL_VERSION'), 3);
            // Connexion au serveur LDAP
            // on met @ car on gère l'erreur après
            $ldapBind = @ldap_bind($ldapConn, getenv('CONFIG_GARDIAN_LDAP_RDN'), getenv('CONFIG_GARDIAN_LDAP_PWD'));
            // Vérification de l'authentification
            if (!$ldapBind) {
                // échec de la connexion avec le compte technique
                $msg = 102; // Echec de l'authentification sur GARDIAN SESAME avec le compte technique.
            } else {
                // Recherche dans la base le NNI renseigné
                $result = ldap_search($ldapConn, 'ou=People,dc=gardiansesame', "(|(uid=$nni)(gardianGroupID=$nni))");
                // Récupère le nombre d'entrée trouvée
                $nbr = ldap_count_entries($ldapConn, $result);
                // Test si il y a des entrées
                if ($nbr) {
                    // Renvoi les résultats de la recherche de l'utilisateur
                    $userEntries = ldap_get_entries($ldapConn, $result);
                    // DN de l'utilisateur
                    $dn = $userEntries[0]['dn'];
                    // Vérifie les identifiants de l'utilisateur
                    // on met @ car on gère les erreurs après
                    $userExist = @ldap_bind($ldapConn, $dn, $pwd);
                    // Retourne le code réponse
                    $errNumero = ldap_errno($ldapConn);
                    // si erreur trouvée, on la retourne
                    if ($errNumero != 0) {
                        switch ($errNumero) {
                            case 19:
                                $msg = 103; // Mot de passe SESAME vérouillé.
                                break;
                            case 32:
                                $msg = 104; // Le compte utilisateur n'a pas été trouvé dans l'annuaire.
                                break;
                            case 34:
                                $msg = 105; // Le DN de l'utilisateur n'est pas valide. Contacter l'administrateur.
                                break;
                            case 49:
                                $msg = 106; // Identifiant incorrect ou mot de passe SESAME expiré.
                                break;
                            default:
                                $msg = 107; // Erreur inconnue.
                                break;
                        }
                    } else {
                        // le couple NNI - Mot de passe SESAME est correct
                        // on finit par vérifier qu'il ne s'agit pas d'un externe
                        $email = (empty($userEntries[0]['mail'][0])) ? null : $userEntries[0]['mail'][0];
                        if ($email === null) {
                            // compte nominatif :
                            $msg = true;
                        } elseif (strpos($email, '-externe.') === false) {
                            // interne :
                            $msg = true;
                        } else {
                            $external = new External($this->db);
                            $msg =  $external->checkExternalEmail($email);
                        }
                    }
                } else {
                    // on n'a pas trouvé cet utilisateur
                    $msg = 108; // Cet utilisateur n'existe pas.
                }
            }
            // on ferme la connexion
            ldap_unbind($ldapConn);
        }
        if ($msg === true) {
            $roles = ['USER'];
            $admin = new Administrator($this->db);

            if (isset($email) && ($admin->checkAdmin($email) === true)) {
                $roles[] = 'ADMIN';
            }

            $entity = EntityService::findEntity($userEntries[0]['gardianhierarchie'][0]);

            $_SESSION['user'] = array(
                'nni' => $userEntries[0]['uid'][0],
                'email' => $email,
                'direction' => $entity[1],
                'entity_code' => $entity[0],
                'full_name' =>  $userEntries[0]['cn'][0],
                'roles' => $roles
            );

            return $this->response(
                [
                    'logged' => true,
                    'data' => [
                        'nni' => $_SESSION['user']['nni'],
                        'email' => $_SESSION['user']['email'],
                        'direction' => $_SESSION['user']['direction'],
                        'entity_code' => $_SESSION['user']['entity_code'],
                        'full_name' =>  $userEntries[0]['cn'][0],
                        'roles' => $roles
                    ]

                ]
            );
        } else {
            return $this->response(
                [
                    'logged' => false,
                    'answer' => $msg
                ]
            );
        }
    }

    public function logout()
    {
        session_start();
        unset($_SESSION['user']);
        return $this->response(
            [
                'logged' => false,
                'answer' => 'Vous avez été déconnecté.'
            ]
        );
    }
}

# Mesures appliquées

- Deux racines publiques distinctes : le site vitrine ne contient aucun code de base de données, d'authentification ou d'accès à l'annuaire.
- PDO avec paramètres liés et émulation désactivée ; aucun champ utilisateur n'est concaténé dans une requête SQL.
- Contrôle serveur de la taille et du type des données, des identifiants, courriels, horaires et transitions. Le contrôle navigateur ne remplace pas celui de PHP.
- Chaque accès aux clients, rendez-vous, demandes et historiques est limité au périmètre autorisé. Un identifiant deviné ne donne pas accès à un autre dossier.
- Les rôles viennent des groupes AD validés. Aucun rôle n'est accepté depuis un formulaire. Direction et conformité n'ont pas d'accès global implicite.
- Authentification LDAPS avec autorité de certification vérifiée. Aucun mot de passe AD stocké. Les comptes sont créés dans AD, sans inscription publique.
- Cookie de session Secure/HttpOnly/SameSite=Strict, renouvellement à la connexion, expiration après quinze minutes d'inactivité et huit heures au maximum.
- État AD, groupe et version du mot de passe vérifiés à chaque requête ; panne annuaire = refus de l'accès métier. Le compte applicatif désactivé reste bloqué.
- Jeton CSRF aléatoire requis sur les mutations, connexion comprise. Le client récupère un nouveau jeton après la connexion.
- Limitation des tentatives de connexion via compteurs serveur partagés et verrouillés. Le reverse proxy doit transmettre un REMOTE_ADDR fiable ; aucune confiance automatique dans X-Forwarded-For.
- Données affichées échappées avant insertion HTML ; pas de mots de passe ou de cookies dans localStorage. Le stockage local est réservé à la démo fictive explicitement signalée.
- CSP sans scripts distants ou inline, refus d'intégration en iframe, no-store sur l'espace interne, aucun CORS ouvert. Police Inter hébergée localement.
- Contrainte unique sur le conseiller et le créneau non annulé pour arbitrer les créations simultanées.
- Création de demande et premier historique atomiques ; verrouillage de la demande pendant les transitions concurrentes.
- Aucun détail SQL/LDAP, mot de passe ou trace complète renvoyé au navigateur. Les journaux d'erreur ne mentionnent que la classe de l'erreur interne.
- Pas de suppression d'historique ou de paiement ; application limitée aux données fictives du projet.

## Validation et limites

La syntaxe PHP et les règles de créneaux/droits ont été testées localement. La vérification LDAP effective dépend de l'AD, des groupes, du compte de lecture et de l'autorité installés dans le laboratoire. Le test concurrent sur MariaDB, les en-têtes HTTPS Apache et les tests de panne doivent être exécutés sur les VM.

Le code ne remplace pas la segmentation réseau, les GPO, les mises à jour, les sauvegardes et les contrôles trimestriels du cahier des charges. Une revue avant mise en service et les tests F01 à F11 restent nécessaires.

Références : [options TLS LDAP PHP](https://www.php.net/manual/en/function.ldap-set-option.php), [constantes de validation des certificats](https://www.php.net/manual/en/ldap.constants.php), [paramètres des cookies de session](https://www.php.net/manual/en/function.session-set-cookie-params.php).

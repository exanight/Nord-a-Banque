# Installation dans le laboratoire

## 1. Site vitrine sur SRV-WEB01

Copier **uniquement** le contenu de `site/public/` dans `/var/www/nordea-site/`. Configurer Apache avec ce DocumentRoot. Le site fonctionne sans PHP, sans base et sans connexion à l'annuaire. Ne pas copier l'espace équipe sur la VM publique.

## 2. Application sur SRV-APP01

Installer depuis les dépôts Debian : Apache, PHP 8.2+ et les extensions PDO MySQL, LDAP et mbstring, ainsi que MariaDB. Vérifier les versions réellement présentes. Déployer le projet dans `/srv/nordea/`, avec le DocumentRoot **`/srv/nordea/app/public`**. Node n'est pas nécessaire en exploitation : les fichiers HTML sont déjà générés.

Exemple de paquets, à adapter à la version Debian du laboratoire :

```sh
sudo apt update
sudo apt install apache2 mariadb-server php libapache2-mod-php php-mysql php-ldap php-mbstring
sudo a2enmod ssl headers
```

Créer un hôte HTTPS dédié avec un certificat émis par l'autorité du laboratoire, reconnu par les postes. Rediriger HTTP vers HTTPS. Ne pas désactiver les vérifications TLS.

Configuration à inclure dans le VirtualHost HTTPS existant après avoir configuré ses certificats :

```apache
DocumentRoot /srv/nordea/app/public
<Directory /srv/nordea/app/public>
    Options -Indexes
    AllowOverride FileInfo AuthConfig Options
    Require all granted
    AddType text/javascript .mjs
</Directory>
```

Restreindre cet hôte au réseau interne avec pfSense et le pare-feu serveur. Le fichier `.htaccess` ajoute les en-têtes de sécurité. La version de démonstration est réservée à l'accès local Apache ; elle n'est pas la connexion réelle.

## 3. Base de données

Pour une nouvelle base : importer `database/001_schema.sql`, puis `002_donnees.sql` uniquement pour la démonstration fictive, puis `003_acces_dossiers.sql` après avoir sélectionné `nordea_banque`.

**Si ta base du livrable 4 existe déjà :** ne pas relancer 001 ou 002 si elles sont déjà importées. Appliquer uniquement 003, une fois. Sauvegarder la base avant toute migration.

Le script 001 cible MariaDB 10.11+ ou MySQL 8.0.16+. Vérifier que la contrainte `uq_rdv_conseiller_creneau` et les CHECK sont présents. MariaDB reste lié à 127.0.0.1. Créer le compte SQL hors du dépôt, avec un mot de passe propre au laboratoire ; ne pas utiliser root dans PHP.

Accorder seulement : SELECT, INSERT, UPDATE sur CLIENT et RENDEZ_VOUS ; SELECT, INSERT sur DEMANDE et HISTORIQUE_DEMANDE ; SELECT, INSERT, UPDATE sur UTILISATEUR ; SELECT sur ACCES_DOSSIER. Pas de DROP, ALTER ou GRANT pour l'application. Le responsable informatique gère les droits de dossiers avec un compte d'administration distinct.

## 4. Annuaire et configuration privée

Copier `config/example.php` vers `config/local.php` puis adapter le domaine, le compte LDAP de lecture et les noms de groupes validés. Fournir les secrets via l'environnement du service : `NORDEA_DB_USER`, `NORDEA_DB_PASSWORD`, `NORDEA_LDAP_BIND_DN`, `NORDEA_LDAP_BIND_PASSWORD`. Aucune valeur secrète n'est fournie dans le projet. Ne pas transmettre les secrets dans la conversation.

Installer le certificat de l'autorité AD à `/etc/ssl/certs/nordea-ca.pem`. Le certificat du contrôleur doit correspondre au nom DNS configuré. LDAP non chiffré est refusé. Créer les six groupes applicatifs proposés ou adapter leur DN. L'application attend un groupe métier direct unique ; l'appartenance à des groupes imbriqués n'est pas résolue.

Au premier login, le salarié autorisé est enregistré avec son véritable objectGUID AD. Les GUID du jeu de données sont fictifs : réaffecter les clients de test au vrai GUID du conseiller via le compte SQL d'administration. Ne pas remplacer les mots de passe AD par des mots de passe locaux. L'inscription est gérée dans AD, jamais par un formulaire public.

Les profils direction, conformité et administration ont besoin d'entrées explicites dans ACCES_DOSSIER. Pour l'administration, activer `gestion_demandes` seulement sur les dossiers validés. Les conseillers voient leurs clients ; l'accueil voit les coordonnées et les rendez-vous mais aucune demande.

Limiter les droits de `config/local.php` à l'administrateur et au groupe du service PHP. Le code doit être en lecture seule pour Apache. Créer `/srv/nordea/var` avec le propriétaire du service PHP et des droits 0700 : ce dossier stocke les compteurs de connexion verrouillés. Le maintenir hors de la racine web. Prévoir une purge quotidienne des anciens fichiers `login-*.json` (après leur fenêtre de quinze minutes), par l'administrateur, sans supprimer un fichier encore utilisé.

## 5. Vérification avant démonstration

Tester : connexion valide/invalide, déconnexion, session expirée, compte désactivé, retrait de groupe, dossier d'un autre conseiller, accès de l'accueil aux demandes refusé, deux créations simultanées sur le même créneau, annulation puis réutilisation, changement de statut et historique, panne AD avec refus d'accès, restauration de la base. Ne pas déclarer F07 validé avant ces essais.

La désactivation AD et le changement de mot de passe sont revérifiés à chaque requête authentifiée ; les sessions ouvertes ne donnent plus accès lors de la requête suivante. Les droits SQL d'affectation et la synchronisation des autres conseillers doivent également être maintenus lors des mouvements de personnel.

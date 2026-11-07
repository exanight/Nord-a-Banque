<?php
declare(strict_types=1);
// Copier vers config/local.php (ignore par Git), HORS des deux DocumentRoot.
// Lire les secrets depuis l'environnement du service Apache/PHP.
return [
    'db_dsn' => 'mysql:host=127.0.0.1;dbname=nordea_banque;charset=utf8mb4',
    'db_user' => getenv('NORDEA_DB_USER') ?: 'nordea_app',
    'db_password' => getenv('NORDEA_DB_PASSWORD') ?: '',
    'ldap_url' => 'ldaps://srv-ad01.ad.nordea.test:636',
    'ldap_base' => 'DC=ad,DC=nordea,DC=test',
    'ldap_bind_dn' => getenv('NORDEA_LDAP_BIND_DN') ?: '',
    'ldap_bind_password' => getenv('NORDEA_LDAP_BIND_PASSWORD') ?: '',
    'ldap_ca' => '/etc/ssl/certs/nordea-ca.pem',
    // Noms de groupes PROPOSES : les creer/adapter et faire valider les droits.
    // Appartenance directe requise. Un seul groupe metier par salarie.
    'groups' => [
        'direction' => 'CN=APP-Direction,OU=Groupes,DC=ad,DC=nordea,DC=test',
        'conseiller_particuliers' => 'CN=APP-Conseillers-Particuliers,OU=Groupes,DC=ad,DC=nordea,DC=test',
        'conseiller_professionnels' => 'CN=APP-Conseillers-Professionnels,OU=Groupes,DC=ad,DC=nordea,DC=test',
        'accueil' => 'CN=APP-Accueil,OU=Groupes,DC=ad,DC=nordea,DC=test',
        'administration' => 'CN=APP-Administration,OU=Groupes,DC=ad,DC=nordea,DC=test',
        'conformite' => 'CN=APP-Conformite,OU=Groupes,DC=ad,DC=nordea,DC=test',
    ],
    'session_idle_seconds' => 900,
    'session_max_seconds' => 28800,
];

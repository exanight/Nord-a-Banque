# Vérifications

## Exécutées localement

- Construction des 5 pages publiques et des 2 entrées équipe (réelle et démonstration).
- Vérification syntaxique des fichiers PHP avec PHP 8.4.27 CLI.
- 5 tests JavaScript : durée et dernier créneau, dates impossibles/week-end/horaires, doublon/annulation/conseillers distincts, transitions, échappement HTML.
- 14 vérifications PHP : créneaux serveur et politiques d'accès sur une base SQLite en mémoire. Ces tests de droits ne constituent pas un essai de MariaDB.
- Navigateur : création d'un rendez-vous fictif, refus d'un doublon, modification de son horaire, ouverture de la confirmation et annulation, passage d'une demande à « en cours » puis « clôturée » avec conservation de trois événements.
- Contrôle visuel de l'accueil à 1440 px et 390 px ; ouverture du menu mobile.

Commandes reproductibles :

```sh
npm run build
npm test
php -d extension=pdo_sqlite tests/backend.php
```

Selon l'installation, PDO SQLite et mbstring peuvent être déjà chargés ; ne pas charger deux fois les extensions. Les extensions SQLite servent uniquement aux tests locaux et ne sont pas nécessaires en exploitation MariaDB.

## À exécuter sur SRV-APP01

| Test | Résultat attendu |
|---|---|
| AD valide / mot de passe faux | Connexion réussie / refus 401 |
| Sans session sur GET clients | Refus 401 |
| POST sans CSRF | Refus 403 |
| Compte désactivé après connexion | Refus à la requête suivante |
| Client d'un autre conseiller | Refus 404, données absentes des listes |
| Accueil sur GET requests | Refus 403 |
| Deux POST simultanés, même conseiller/créneau | Un succès 201, un conflit 409 |
| Deux transitions simultanées identiques | Un succès 201, un conflit 409 |
| LDAP indisponible ou certificat invalide | Accès métier refusé |
| Restauration MariaDB | Tables, relations et historique conservés |

Prendre les captures sans mots de passe ni cookies. Les captures fournies portent uniquement sur la démonstration fictive et l'intégration visuelle. Aucun test AD/MariaDB n'est déclaré réussi avant exécution.

## Mise à jour Liquid Glass
Trois lumières de fond animées, surfaces translucides avec flou 34 px, reflets liés au pointeur, légère inclinaison, brillance et onde au clic, apparitions au défilement. Reflet et inclinaison observés dans le navigateur ; absence de débordement à 390 px ; navigation mobile testée. La réduction des animations est prise en charge par CSS et JavaScript. Les cinq tests du domaine passent après reconstruction.


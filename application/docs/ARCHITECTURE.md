# Choix technique et architecture

## WordPress ou développement sur mesure

| Critère | WordPress | HTML/CSS/JS + PHP/MariaDB |
|---|---|---|
| Site vitrine | Rapide avec un thème adapté | 5 pages légères, fidèles à Figma |
| Rendez-vous simultanés et historique | Extensions et adaptation nécessaires | Règles contrôlées dans PHP et la base |
| Connexion AD et droits par dossier | Intégrations supplémentaires | Module LDAPS et politique métier explicites |
| Maintenance | CMS, thème et extensions à maintenir | PHP, MariaDB et code du projet à maintenir |
| Compétences | Administration du CMS | HTML, CSS, JS, PHP, SQL et administration AD |
| Personnalisation | Dépend du thème et des extensions | Charte Nordéa et effet Liquid Glass maîtrisés |

**Choix retenu : développement sur mesure**, pour rester cohérent avec le cahier des charges et limiter les dépendances. Le site public est statique ; l'application interne consomme son API PHP JSON, sur la même origine pour éviter CORS et les jetons dans le navigateur. Ce choix nécessite davantage de développement et de tests qu'un site vitrine WordPress.

## Organisation

```text
site/public/            Site public autonome, sans accès métier
app/public/            Interface équipe et entrée HTTP api.php
app/public/assets/     JavaScript, CSS, police, démo fictive
app/src/Http.php        Validation des entrées et réponses JSON
app/src/Auth.php        Sessions, CSRF, limitation de tentatives
app/src/Directory.php   Connexion LDAPS, état du compte et groupes AD
app/src/Policy.php      Profils et périmètres de dossiers autorisés
app/src/Service.php     Clients, rendez-vous et demandes
app/src/Database.php    PDO et transactions
config/local.php       Configuration privée, ignorée par Git
database/              Création, exemples et migration des accès
shared/                Sources communes de l'intégration
scripts/               Construction et aperçu local
tests/                 Tests du domaine et des autorisations
docs/                  Installation, API, sécurité et preuves
```

Une interaction suit : formulaire JS → API PHP → validation/authentification → politique d'accès → service métier → PDO/MariaDB → réponse JSON → actualisation de l'interface.

Le rendu initial des pages publiques est en HTML. L'espace équipe est une interface JavaScript servie par le même serveur que son API. Le livrable conditionnel « conception backend si monolithe » est documenté ici pour expliquer l'organisation, mais le projet retient l'API pour l'interface interne.

## Ajout au modèle de données

La migration `003_acces_dossiers.sql` ajoute **ACCES_DOSSIER**, une association utilisateur–client, pour appliquer les dossiers autorisés de la direction, de l'administration et de la conformité. Sa clé primaire est le couple `(identifiant_ad, id_client)`. `gestion_demandes` permet les opérations administratives sur les demandes explicitement attribuées. Il n'accorde jamais un rôle ou un droit global.

Le modèle à cinq tables reste le cœur métier ; cette sixième table complète la gestion des droits exigée par F07. Mettre à jour le schéma du dossier avec cette association avant la remise finale. Les responsables n'ont aucun accès implicite à tous les dossiers.

Les horaires 9 h–17 h et les départs à l'heure ou à la demi-heure restent des hypothèses à faire valider. Aucun service de messagerie n'est nécessaire pour enregistrer un rendez-vous.

# Nordéa Banque — site et application interne

Intégration de la [maquette Figma](https://www.figma.com/design/oZRoHJ4HoQvFZWTdzMmVTC/Projet-Fil-Rouge), avec le vert sauge, la police Inter locale, les espacements et les cartes de la charte. L'effet Liquid Glass ajoute de la transparence, un flou de fond, des reflets et une bordure claire. Un fond opaque de remplacement et le respect de la réduction des animations sont prévus.

## Essayer sur ce PC

Pour consulter uniquement le site vitrine, ouvrir `site/public/index.html` dans un navigateur. Ses pages et sa navigation fonctionnent sans installation.

Depuis ce dossier, avec Node.js 22.16+ :

```sh
npm run build
npm run preview
```

- Site public : http://127.0.0.1:4173/
- Démonstration équipe : http://127.0.0.1:4173/equipe/demo.html

La démonstration est explicitement distincte du backend. Elle utilise exclusivement des données fictives dans le stockage du navigateur. Elle permet de rechercher/créer/modifier des clients, créer/modifier/annuler des rendez-vous et suivre les demandes avec historique. Pour repartir des exemples initiaux, supprimer uniquement la clé `nordea-demo-v1` du stockage du navigateur.

La connexion réelle est dans `app/public/index.html` et nécessite Apache HTTPS, PHP, MariaDB et Active Directory configurés. Le serveur de prévisualisation ne sait pas exécuter PHP. Ne pas utiliser son fonctionnement comme preuve d'authentification AD.

## Déployer selon le cahier des charges

- **SRV-WEB01 (DMZ)** : copier seulement `site/public/` vers le répertoire public d'Apache. Aucune connexion à la base ou à AD ; aucun portail client ou formulaire de réservation.
- **SRV-APP01 (interne)** : copier le projet, mais définir le DocumentRoot Apache sur `app/public/` uniquement. MariaDB doit écouter sur `127.0.0.1` ; l'annuaire est joint par LDAPS avec certificat vérifié.
- Ne pas publier le répertoire du projet entier. `config/`, `database/`, `var/`, `tests/` et `.git/` restent hors de la racine web.

Voir [INSTALLATION.md](docs/INSTALLATION.md), [ARCHITECTURE.md](docs/ARCHITECTURE.md), [API.md](docs/API.md), [SECURITE.md](docs/SECURITE.md) et [TESTS.md](docs/TESTS.md).

## Livrables de développement

| Livrable | Éléments fournis |
|---|---|
| 1 — Choix technique | Comparaison et choix sur mesure dans `docs/ARCHITECTURE.md` |
| 2 — API | Routes PHP dans `app/public/api.php`, traitements dans `app/src/` |
| 3 — Backend | Arborescence et responsabilités documentées ; API et interface partagent le même service |
| 4 — HTML/CSS | 5 pages publiques, écrans équipe, styles et police locaux |
| 5 — JavaScript | Navigation, recherche, filtres, formulaires, validation, confirmations, messages |
| 7 — Authentification | Connexion AD chiffrée, sessions, déconnexion et vérification du compte à chaque requête |
| 8 — Sécurité | Contrôles serveur, requêtes préparées, CSRF, CSP, droits par dossier |
| 9 — Git | Dépôt local et étapes de développement ; publication GitHub à effectuer dans ton dépôt |
| 10 — Preuves | Captures locales du site et de la démonstration, plus compte rendu des tests |

**Limites de validation :** la connexion réelle AD, le déploiement Apache/HTTPS et l'exécution métier sur MariaDB doivent encore être testés sur tes VM. Les résultats d'authentification et de concurrence ne sont pas présentés comme validés en production.

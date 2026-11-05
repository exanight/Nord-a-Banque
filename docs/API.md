# API de l'espace équipe

## Convention

Point d'entrée sur **SRV-APP01 uniquement** : `https://app.ad.nordea.test/api.php?route=...` (nom proposé à configurer). Les ressources utilisent les identifiants du modèle SQL. Corps JSON UTF-8 ; dates de rendez-vous `YYYY-MM-DDTHH:mm`, en heure locale Europe/Paris. Les secondes sont interdites à la saisie des créneaux. L'application est dimensionnée pour la maquette de 15 salariés ; les listes sont renvoyées sans pagination. Prévoir une pagination avant d'augmenter le volume.

Toutes les réponses réussies suivent `{"data": ...}`. Les erreurs suivent `{"error":{"status":422,"message":"..."}}`.

## Authentification

1. Appeler `GET ...?route=session` avec conservation des cookies. La réponse initiale contient `user: null` et `csrf`.
2. Envoyer ce jeton dans `X-CSRF-Token` sur chaque POST/PATCH, **y compris la connexion**.
3. Connexion via `POST ...?route=login` : `{"username":"alex.bernard","password":"<mot de passe saisi par l’utilisateur>"}`. Ne pas enregistrer ce corps dans les captures, journaux ou collections partagées.
4. Le serveur vérifie AD par LDAPS, renouvelle l'identifiant de session et le jeton CSRF. Utiliser le nouveau `csrf` retourné.
5. Le cookie `nordea_session` est Secure, HttpOnly, SameSite=Strict. Aucun mot de passe ni jeton de session dans localStorage.

Dans Postman, activer le cookie jar, utiliser HTTPS avec l'autorité de certification du laboratoire reconnue et enregistrer uniquement le jeton CSRF dans une variable locale non exportée. Ne pas désactiver la vérification TLS.

## Routes

Les identifiants dans les chemins ci-dessous sont transmis dans le paramètre `route`.

| Méthode | Route | Entrées | Résultat / droits |
|---|---|---|---|
| GET | `session` | Aucune | 200 : utilisateur courant ou null, jeton CSRF |
| POST | `login` | username, password | 200 : user et nouveau csrf ; compte AD autorisé |
| POST | `logout` | Objet vide | 200 : session détruite |
| GET | `bootstrap` | Aucune | 200 : user, can, clients, advisors, appointments, requests, history filtrés |
| GET | `clients` | Aucune | 200 : clients autorisés |
| GET | `clients/{id}` | Identifiant entier positif | 200 : client autorisé |
| POST | `clients` | type_client, nom_ou_raison_sociale, prenom, email, telephone, conseiller_ad | 201 : id_client ; accueil ou conseiller |
| PATCH | `clients/{id}` | Même objet complet que la création | 200 : id_client ; accueil ou conseiller du dossier |
| GET | `appointments` | Aucune | 200 : rendez-vous des dossiers autorisés |
| GET | `appointments/{id}` | Identifiant entier positif | 200 : rendez-vous autorisé |
| POST | `appointments` | id_client, conseiller_ad, debut, objet | 201 : id_rdv ; accueil ou conseiller |
| PATCH | `appointments/{id}` | Données complètes de rendez-vous OU `{"statut":"annule"}` | 200 : id_rdv ; seulement un rendez-vous prévu |
| GET | `requests` | Aucune | 200 : demandes et statut courant ; accueil exclu |
| GET | `requests/{id}` | Identifiant entier positif | 200 : demande autorisée ; accueil exclu |
| POST | `requests` | id_client, objet, description facultative | 201 : id_demande ; conseiller ou administration avec droit explicite |
| POST | `requests/{id}/transitions` | statut, commentaire facultatif | 201 : id_demande et statut ; mêmes droits d'écriture |

Le PATCH client est volontairement limité à la mise à jour de la fiche complète : ne pas omettre les champs obligatoires. Les chaînes facultatives vides deviennent NULL. Les autres champs transmis ne sont pas utilisés. Aucun endpoint ne permet de changer les groupes AD ou d'attribuer des droits.

Pas de DELETE métier : une annulation conserve le rendez-vous et libère le créneau ; les historiques sont conservés. Aucune inscription publique, aucun compte bancaire ou paiement.

## Exemple : créer un rendez-vous

```http
POST /api.php?route=appointments
Content-Type: application/json
X-CSRF-Token: <jeton de la session>
```

```json
{
  "id_client": 1001,
  "conseiller_ad": "00000000-0000-0000-0000-000000000002",
  "debut": "2026-11-09T10:00",
  "objet": "Suivi du dossier"
}
```

Les GUID ci-dessus viennent du jeu fictif. Les remplacer par ceux des comptes AD réels de la maquette pour tester la connexion effective.

Réponse illustrative `201 Created` :

```json
{"data":{"id_rdv":2009}}
```

Réponse illustrative si un autre rendez-vous non annulé occupe le même créneau :

```json
{"error":{"status":409,"message":"Ce créneau est déjà réservé. Choisissez-en un autre."}}
```

## Exemple : suivre une demande

```http
POST /api.php?route=requests/3002/transitions
Content-Type: application/json
X-CSRF-Token: <jeton de la session>
```

```json
{"statut":"en_cours","commentaire":"Pièces administratives en cours de vérification."}
```

Réponse illustrative `201 Created` :

```json
{"data":{"id_demande":3002,"statut":"en_cours"}}
```

Seules les transitions `nouvelle → en_cours → cloturee` sont autorisées. L'auteur vient de la session, jamais du corps reçu. L'historique complet des dossiers autorisés est fourni dans `bootstrap.history` ; l'état courant vient de la dernière ligne classée par date puis identifiant.

## Codes et erreurs

| Code | Cas |
|---|---|
| 200 | Lecture, connexion, modification ou déconnexion réussie |
| 201 | Création d'un client, rendez-vous, demande ou changement de statut |
| 400 | JSON invalide ou accès HTTP sans HTTPS |
| 401 | Session absente/expirée, identifiants erronés ou compte AD désactivé |
| 403 | Profil, groupe métier ou jeton CSRF non autorisé |
| 404 | Route ou dossier introuvable/non autorisé |
| 405 | Méthode HTTP non acceptée |
| 409 | Conflit de créneau ou transition incompatible |
| 413 | Corps dépassant 16 Ko |
| 415 | Corps non JSON |
| 422 | Champs ou créneau invalides |
| 429 | Plus de dix tentatives de connexion sur quinze minutes par IP ou identifiant |
| 500/503 | Service ou configuration indisponible, sans détail technique exposé |

Les tests de bout en bout AD/MariaDB restent à réaliser sur SRV-APP01. Les réponses ci-dessus sont des exemples de contrat, pas des captures de résultats réels.

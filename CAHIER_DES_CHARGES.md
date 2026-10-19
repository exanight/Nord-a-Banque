# Cahier des charges simplifié — Nordéa Banque

**Projet fil rouge : modernisation du système d’information d’une agence bancaire fictive**  
**Version :** 1.0 — 19 octobre 2026  
**Commanditaire :** Nordéa Banque, représentée par sa direction  
**Réalisateur :** HexaShield, dans le cadre du projet scolaire  
**Statut :** proposition à valider avant réalisation

## 1. Contexte et besoin

Nordéa Banque est une banque régionale fictive située à Paris. Son agence accompagne les particuliers et les petites entreprises dans la gestion de leurs comptes, de leur épargne et de leurs demandes de crédit.

Elle compte 15 collaborateurs : une personne à la direction, cinq conseillers particuliers, trois conseillers professionnels, deux personnes à l’accueil, deux à l’administration et deux aux risques et à la conformité.

Aujourd’hui, les documents sont dispersés, les droits d’accès sont mal organisés et les demandes internes passent principalement par courriel. Les sauvegardes sont insuffisamment testées et les incidents informatiques manquent de suivi.

**Le besoin est de mettre en place une maquette de système d’information sécurisé, administrable et restaurable, adaptée aux 15 collaborateurs, ainsi qu’un site vitrine et une application de rendez-vous et de suivi des demandes clients.**

## 2. Objectifs

- Centraliser les comptes et les ressources, avec des droits adaptés aux fonctions.
- Séparer les réseaux des salariés, des serveurs, des visiteurs et de l’administration.
- Présenter l’agence et ses services sur un site public.
- Enregistrer les rendez-vous et suivre les demandes clients dans une application reliée à une base de données et à un service externe de courriels.
- Organiser l’assistance, superviser les services et démontrer leur restauration après un incident.
- Privilégier les solutions françaises, puis européennes ou open source, en justifiant les choix et leurs dépendances.

## 3. Périmètre

### Éléments inclus

Le projet comprend une infrastructure virtualisée de six serveurs, un pare-feu dédié, un stockage de sauvegarde distinct, un annuaire de comptes, des partages de fichiers, un site vitrine, une application métier, un outil de tickets, une supervision et les procédures associées. Une formation et une recette complètent la livraison.

La maquette utilisera uniquement des clients et des données fictifs. Les courriels de test seront envoyés à des adresses autorisées.

### Éléments exclus

- Opérations bancaires réelles : paiements, virements, tenue de comptes et décisions de crédit.
- Connexion à un système bancaire réel et utilisation de données clients réelles.
- Application mobile native et portail client complet.
- Haute disponibilité automatique entre plusieurs hôtes physiques et exploitation réelle en continu.

### Hypothèse fonctionnelle à confirmer

Pour limiter le périmètre, le site vitrine sera public et l’application métier sera réservée aux collaborateurs authentifiés sur le réseau interne. L’accueil et les conseillers saisiront les rendez-vous pour les clients. Une prise de rendez-vous directement accessible au public nécessiterait de préciser le parcours et la protection de l’application avant de l’ajouter au périmètre.

## 4. Utilisateurs et accès

Chaque collaborateur disposera d’un compte individuel. Les droits seront accordés selon le métier et, lorsque nécessaire, selon les dossiers attribués.

| Profil | Effectif | Accès attendus |
| --- | ---: | --- |
| Direction | 1 | Synthèses, pilotage et validations ; dossiers selon les autorisations définies. |
| Conseillers particuliers | 5 | Rendez-vous et demandes des particuliers qui leur sont attribués. |
| Conseillers professionnels | 3 | Rendez-vous et demandes des entreprises qui leur sont attribués. |
| Accueil | 2 | Coordonnées nécessaires et rendez-vous, sans détail financier. |
| Administration | 2 | Documents administratifs et traitements autorisés. |
| Risques et conformité | 2 | Consultation des dossiers nécessaires aux contrôles et suivi des anomalies. |
| HexaShield | Externe | Administration technique avec des comptes distincts et des accès limités aux interventions autorisées. |

Les créations et modifications de droits seront validées par le responsable métier. Les comptes seront désactivés au départ d’un salarié et les droits revus chaque trimestre.

## 5. Exigences fonctionnelles

| Référence | Besoin attendu | Critère de validation |
| --- | --- | --- |
| F01 | Présenter la banque, ses services, ses coordonnées et son agence sur un site vitrine. | Les pages et liens fonctionnent sur ordinateur et mobile ; le site est accessible en HTTPS. |
| F02 | Centraliser les 15 comptes et appliquer les droits aux partages et à l’application. | Un compte de chaque profil est testé ; les accès autorisés réussissent et les accès interdits sont refusés. Un compte désactivé perd son accès. |
| F03 | Créer, consulter, modifier et annuler un rendez-vous avec un client fictif, un conseiller, un motif, une date et un horaire. | Un parcours complet est démontré ; deux rendez-vous actifs ne peuvent pas occuper le même créneau d’un conseiller. |
| F04 | Enregistrer et suivre une demande client avec une référence, un type, un responsable et un statut. | Une demande passe de « nouvelle » à « en cours », puis « clôturée » ; les changements sont conservés dans un historique. |
| F05 | Stocker durablement les rendez-vous et demandes dans une base de données. | Les données enregistrées restent consultables après redémarrage de l’application. |
| F06 | Transmettre une notification de rendez-vous par un service externe de courriels. | Un courriel de test est reçu. En cas d’échec externe, le rendez-vous reste enregistré, l’échec est visible par un utilisateur autorisé et une relance est possible. |
| F07 | Centraliser les incidents et demandes informatiques dans GLPI. | Un incident et une demande sont qualifiés, priorisés, affectés, traités et clôturés avec leur historique. Une demande de droits comporte une approbation. |
| F08 | Superviser les machines, les services essentiels, le stockage et les interfaces réseau. | L’arrêt volontaire d’un service surveillé déclenche une alerte ; le retour au fonctionnement est constaté et documenté. |
| F09 | Automatiser les sauvegardes et permettre la restauration. | Un fichier, une base et une VM sont restaurés sur un environnement isolé ; leur contenu et leur fonctionnement sont vérifiés. |
| F10 | Fournir des guides et former les utilisateurs. | Les guides utilisateur et administrateur sont remis ; les exercices de connexion, d’accès à une ressource et de création d’un ticket sont réalisés avec des comptes représentatifs. |

Les règles détaillées de rendez-vous, les catégories de demandes et les habilitations seront précisées pendant la conception. Ces fonctions constituent la cible à développer, et non un état déjà réalisé.

## 6. Contraintes techniques et de sécurité

### Architecture retenue pour la maquette

| Composant | Rôle et solutions prévues |
| --- | --- |
| Hôte physique | Proxmox VE sans abonnement, hébergeant les six VM. |
| Pare-feu distinct | OPNsense Community : filtrage, routage et accès VPN avec authentification multifacteur. |
| VM 1 — Web public | Debian, Apache, ModSecurity et OWASP CRS ; HTTPS avec un certificat adapté à l’environnement. |
| VM 2 — Identité | Debian, Samba AD DC, DNS et Kea DHCP. |
| VM 3 — Application | Debian, Apache, PHP et Symfony. |
| VM 4 — Données métier | Debian et MariaDB Community Server. |
| VM 5 — Services internes | Debian, partages Samba et GLPI avec sa base locale. |
| VM 6 — Supervision | Debian et Zabbix avec sa base locale. |
| NAS distinct | TrueNAS Community Edition pour recevoir les sauvegardes. |
| Service externe | Brevo, sous réserve des conditions et quotas de son offre gratuite lors de l’intégration. |

Les versions et ressources matérielles seront arrêtées après vérification des compatibilités et de la capacité disponible. L’application Symfony reste à développer.

### Segmentation et protection

| Zone | Réseau prévu | Usage |
| --- | --- | --- |
| VLAN 10 | `192.168.10.0/24` | Postes des salariés. |
| VLAN 20 | `192.168.20.0/24` | Serveurs internes et NAS. |
| VLAN 30 | `192.168.30.0/24` | Wi-Fi visiteurs, avec accès Internet uniquement. |
| VLAN 40 | `192.168.40.0/24` | DMZ contenant le serveur du site vitrine. |
| VLAN 99 | `192.168.99.0/24` | Administration technique. |

Les échanges entre zones seront bloqués par défaut, puis ouverts selon les besoins documentés. La base métier ne sera accessible que par l’application et les administrateurs autorisés. Les accès aux autres bases seront limités à leurs services et administrateurs respectifs. Les restrictions entre équipements d’un même VLAN seront également appliquées au niveau des serveurs.

Les interfaces web utiliseront HTTPS. L’administration distante passera par un VPN nominatif avec mot de passe et code temporaire. Les secrets seront conservés hors du code source et du repository. Les contrôles de droits seront appliqués côté serveur. Les journaux et tickets devront permettre le diagnostic sans exposer de mots de passe ni de dossiers clients complets.

### Sauvegarde et continuité

Des sauvegardes nocturnes des VM et des exports cohérents des bases seront conservés sur le NAS. Une copie chiffrée sera transférée vers un support externe, déconnecté après transfert et conservé hors site. La rétention proposée est de sept sauvegardes quotidiennes et quatre hebdomadaires, sous réserve de capacité.

Les objectifs proposés pour les services essentiels sont une **perte de données maximale de 24 heures (RPO)** et une **reprise sous 4 heures (RTO)**. Ces objectifs devront être validés par des essais ; la reprise après panne de l’hôte suppose un matériel de remplacement disponible. Le résultat des sauvegardes sera contrôlé chaque jour et une restauration testée avant la recette, puis chaque mois dans le scénario d’exploitation.

La maquette repose sur un seul hôte physique : sa panne arrête les six VM, y compris la supervision. Une procédure de reprise devra préciser l’ordre de restauration des services et leurs dépendances. Un onduleur est prévu pour protéger l’hôte et le NAS.

## 7. Assistance et exploitation

GLPI constituera le point d’entrée des demandes et incidents, avec un circuit inspiré des principes ITIL : enregistrement, qualification, affectation, traitement, résolution et clôture après vérification. En cas d’indisponibilité, un registre provisoire permettra leur saisie ultérieure.

| Priorité | Exemple | Objectif de prise en charge |
| --- | --- | --- |
| Critique | Application indisponible pour toute l’agence. | Sous 30 minutes. |
| Haute | Collaborateur bloqué sans solution de remplacement. | Sous 2 heures. |
| Normale | Demande de matériel ou gêne contournable. | Sous 1 jour ouvré. |

Ces objectifs sont proposés pour une plage de support du lundi au vendredi, de 9 h à 18 h. Ils concernent la prise en charge ; la résolution dépend du diagnostic. Les procédures couvriront les pannes système, réseau et applicatives, les mises à jour et le retour arrière.

## 8. Budget, moyens et organisation

Le budget cible de licences et d’abonnements obligatoires est de **0 € pour les fonctions de la maquette**. Les éditions gratuites seront utilisées sans version d’essai ni extension payante nécessaire. Les conditions du service externe devront être vérifiées avant intégration.

Le matériel, l’onduleur, les supports externes, la connexion Internet, un éventuel nom de domaine et le temps de réalisation sont à chiffrer séparément. La copie hors site suppose un responsable désigné et une rotation effective des supports.

La direction valide les besoins, les droits et la recette. HexaShield conçoit, réalise, teste et documente la solution. Des comptes représentatifs de chaque service permettent la validation des parcours métier. Dans le cadre scolaire, ces rôles peuvent être simulés.

## 9. Planning prévisionnel

| Période | Travaux et jalon attendu |
| --- | --- |
| Semaine 1 | Validation du besoin, du périmètre, de l’architecture et de la matrice des accès. |
| Semaine 2 | Installation de l’infrastructure, des réseaux et de l’annuaire. |
| Semaine 3 | Réalisation du site vitrine, de l’application, de la base et de l’intégration courriel. |
| Semaine 4 | Installation de GLPI, de la supervision et des sauvegardes ; rédaction des procédures. |
| Semaine 5 | Tests fonctionnels et techniques, restauration et corrections. |
| Semaine 6 | Démonstration, formation, recette finale et remise du dossier. |

Ce calendrier de six semaines est une proposition à adapter aux échéances scolaires. Un point hebdomadaire permettra de suivre l’avancement et les difficultés.

## 10. Livrables et recette

Les livrables attendus sont :

1. Le présent cahier des charges simplifié, à déposer dans le repository sous le nom `CAHIER_DES_CHARGES.md`.
2. Le schéma réseau, le plan d’adressage, l’inventaire, les règles de filtrage et la matrice des accès.
3. Le code du site et de l’application, le schéma de données et les instructions d’installation, sans secrets ni données réelles.
4. Les procédures d’administration, de sauvegarde, de restauration, d’incident et de retour arrière.
5. Les fiches de tests avec résultats, preuves, dates, testeur et mesures de reprise.
6. La charte informatique, les guides et les supports de formation.
7. Le bilan du projet, les limites connues et le procès-verbal de recette.

La recette sera acceptée lorsque toutes les exigences F01 à F10 auront été vérifiées, que les tests critiques de droits, de segmentation et de restauration auront réussi, et qu’aucun incident critique ne restera ouvert. Les objectifs RTO et RPO devront être mesurés et respectés, ou faire l’objet d’une réserve explicitement acceptée. Toute autre réserve sera documentée avec son impact et l’action prévue.

Le passage en exploitation sera simulé avec une sauvegarde préalable, une procédure de bascule, des vérifications et un retour arrière préparé. Les résultats non encore vérifiés seront indiqués comme « à mesurer ».

## 11. Points à confirmer avant réalisation

- Validation de l’application réservée aux collaborateurs ou ajout d’une prise de rendez-vous publique.
- Horaires, durée des créneaux, catégories et statuts détaillés des demandes.
- Matériel disponible, capacité de sauvegarde et moyens de reprise après panne de l’hôte.
- Faisabilité de HTTPS public : domaine et validation du certificat, ou solution de certificat pour le laboratoire.
- Conditions du service de courriels, calendrier final et personnes chargées de la recette.


---

*Source de cadrage : note « Nordéa Banque — Logiciels gratuits » fournie pour le projet. Les règles de créneaux, les statuts minimaux et le choix d’un accès métier interne précisent le besoin sous forme de propositions à valider. Ce document décrit les résultats attendus avant réalisation.*

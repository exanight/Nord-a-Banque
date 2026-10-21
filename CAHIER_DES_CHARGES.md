# Cahier des charges — Nordéa Banque

**Projet :** modernisation du système d’information d’une agence bancaire fictive  
**Version :** 2.0 — 20 octobre 2026  
**Commanditaire :** direction de Nordéa Banque  
**Auteur :** le candidat, responsable informatique dans le cadre du projet  
**Destinataire :** Jury  
**Formation :** BTS SIO, principalement SISR  
**Statut :** Projet à réaliser et à présenter au jury

## 1. Contexte et besoin

Nordéa Banque est une banque régionale fictive située à Paris. Elle accompagne les particuliers et les petites entreprises dans la gestion de leurs comptes, de leur épargne et de leurs crédits.

L’agence compte 15 collaborateurs. Ses documents sont dispersés, les droits d’accès sont mal organisés et les incidents informatiques manquent de suivi. Les sauvegardes sont peu testées.

En tant que responsable informatique, je propose une maquette pour centraliser les comptes et les fichiers avec Windows Server, sécuriser le réseau et améliorer le suivi informatique. Ce cahier des charges présente le projet au jury.

## 2. Objectifs

- Centraliser les comptes et les fichiers.
- Adapter les droits aux fonctions des salariés.
- Séparer et sécuriser les réseaux.
- Créer un site vitrine et une application interne simple.
- Suivre les incidents avec GLPI et les services avec Zabbix.
- Sauvegarder les données et tester leur restauration.
- Fournir les guides et les preuves de fonctionnement.

Le projet repose sur les compétences du BTS SIO. Des approfondissements facultatifs pourront être ajoutés une fois la maquette fonctionnelle.

## 3. Périmètre

La maquette comprend six serveurs virtuels, un pare-feu pfSense, deux postes Windows de test et un seul NAS externe à l’hôte Proxmox pour les sauvegardes. Les 15 comptes seront testés sur ces postes.

Elle comprend aussi le site vitrine, l’application interne, GLPI, Zabbix et les procédures d’administration.

Les clients et les données sont fictifs. Les opérations bancaires réelles, le portail client, l’application mobile et la haute disponibilité sont exclus.

Les rendez-vous sont saisis par l’accueil et les conseillers. Le site vitrine peut être présenté dans le laboratoire sans publication sur Internet.

## 4. Utilisateurs et accès

| Profil | Effectif | Accès prévus |
| --- | ---: | --- |
| Direction | 1 | Synthèses, validations et dossiers autorisés. |
| Conseillers particuliers | 5 | Rendez-vous et demandes de leurs clients. |
| Conseillers professionnels | 3 | Rendez-vous et demandes des entreprises suivies. |
| Accueil | 2 | Coordonnées et rendez-vous, sans détail financier. |
| Administration | 2 | Documents et demandes administratives. |
| Risques et conformité | 2 | Dossiers nécessaires aux contrôles. |

J’assure le rôle de responsable informatique pour la maquette, sans modifier ces effectifs. J’utilise un compte d’administration distinct de mon compte courant.

Les droits sont attribués par groupes et validés par la direction. Ils sont contrôlés chaque trimestre. Au départ d’un salarié, son compte est désactivé et ses accès sont retirés.

## 5. Fonctions attendues et tests

| Réf. | Fonction attendue | Test de validation |
| --- | --- | --- |
| F01 | 15 comptes Active Directory et deux postes dans le domaine. | Connexions réussies ; nouvelle connexion réseau refusée avec un compte désactivé. |
| F02 | GPO : mots de passe, verrouillage, lecteurs réseau et pare-feu Windows. | Paramètres appliqués sur les postes et vérifiés avec `gpresult`. |
| F03 | Partages par service et espace commun. | Lecture et modification autorisées selon le profil ; autres accès refusés. |
| F04 | Site vitrine en HTTPS. | Pages lisibles sur ordinateur et mobile ; certificat reconnu. |
| F05 | Création, modification et annulation de rendez-vous. | Parcours complet ; aucun doublon de créneau, même lors de saisies simultanées. |
| F06 | Suivi des demandes clients. | Passage de « nouvelle » à « en cours », puis « clôturée », avec historique. |
| F07 | Connexion à l’application avec un compte AD. | Accès limité au rôle et aux dossiers autorisés. |
| F08 | Gestion des incidents et demandes dans GLPI. | Ticket traité jusqu’à sa clôture ; demande de droits validée. |
| F09 | Supervision des services. | Alerte lors d’une panne et constat du retour à la normale. |
| F10 | Sauvegarde et restauration. | Fichier, base de données et VM restaurés et vérifiés sur un réseau isolé. |
| F11 | Documentation et accompagnement. | Guides remis ; démonstration de connexion, d’accès aux fichiers et de création d’un ticket. |

L’envoi de courriels de rendez-vous est facultatif. Une panne de messagerie ne doit pas empêcher leur enregistrement.

## 6. Architecture technique

Windows Server gère les comptes et les fichiers. Debian héberge les services web et les outils de suivi.

| Équipement          | Solution                       | Rôle                                         |
| ------------------- | ------------------------------ | -------------------------------------------- |
| Hôte physique       | Proxmox VE                     | Hébergement des machines virtuelles.         |
| Pare-feu            | pfSense CE                     | Routage, filtrage et accès Internet.         |
| SRV-AD01            | Windows Server 2025            | Active Directory, DNS, DHCP et GPO.          |
| SRV-FIC01           | Windows Server 2025            | Partages SMB et droits NTFS.                 |
| SRV-WEB01           | Debian et Apache               | Site vitrine.                                |
| SRV-APP01           | Debian, Apache, PHP et MariaDB | Application interne et base de données.      |
| SRV-GLPI01          | Debian et GLPI, avec sa base   | Inventaire et tickets.                       |
| SRV-SUP01           | Debian et Zabbix, avec sa base | Supervision.                                 |
| Deux postes de test | Windows 11 Pro ou Enterprise   | Tests des comptes, des GPO et des accès.     |
| NAS externe | Équipement distinct de l’hôte Proxmox | Sauvegardes uniquement ; aucune VM NAS supplémentaire. |

Windows Server 2022 peut remplacer 2025 selon les licences et les cours. Le pare-feu peut être physique ou constituer une VM supplémentaire.

L’application et sa base sont sur la même VM. La base est accessible uniquement en local. Une panne de l’hôte arrête toutes les VM qu’il héberge.

Prévoir idéalement 48 à 64 Go de mémoire pour l’hôte. Avec 32 Go, il pourra être nécessaire de limiter les VM allumées. Le stockage sera dimensionné selon les données et les sauvegardes à conserver.

## 7. Domaine Windows et fichiers

Le domaine de test sera `ad.nordea.test`. Les utilisateurs, postes et serveurs seront organisés dans Active Directory.

Les droits suivront le principe : **utilisateur → groupe métier → groupe de permission → dossier**.

Les partages prévus sont : Commun, Direction, Conseillers, Accueil, Administration et Conformité. Un tableau précisera les droits de lecture et de modification.

Les stratégies de groupe (GPO) géreront les mots de passe, le verrouillage des sessions, les lecteurs réseau et le pare-feu Windows. Les mises à jour seront planifiées et vérifiées.

Un script PowerShell de création de comptes pourra être ajouté en option.

## 8. Réseau et sécurité

| Zone | Réseau | Usage |
| --- | --- | --- |
| VLAN 10 | `192.168.10.0/24` | Postes des salariés. |
| VLAN 20 | `192.168.20.0/24` | Serveurs internes et NAS externe de sauvegarde. |
| VLAN 40 | `192.168.40.0/24` | DMZ : zone séparée pour le site vitrine. |
| VLAN 99 | `192.168.99.0/24` | Administration informatique. |

pfSense bloque les échanges entre zones, sauf ceux nécessaires aux services. Les règles seront documentées et testées, notamment pour Active Directory.

Les postes utilisent le DNS de SRV-AD01 et un relais DHCP pour recevoir leur adresse IP.

Les règles de sécurité sont les suivantes :

- Aucun accès du site vitrine aux fichiers, à l’annuaire ou à la base métier.
- Administration depuis le VLAN 99.
- Accès au NAS externe réservé aux besoins de sauvegarde.
- Pare-feu des serveurs actifs, y compris entre machines d’un même VLAN.
- HTTPS avec des certificats reconnus par les postes de test.

Un accès distant pourra être ajouté par VPN avec double authentification. RDP ne sera pas exposé sur Internet.

## 9. Site vitrine et application

Le site vitrine présente l’agence, ses services, ses horaires et ses coordonnées fictives. Il est réalisé en HTML/CSS et adapté aux mobiles.

L’application interne utilise PHP et MariaDB. Elle gère les clients fictifs, les rendez-vous, les demandes et leur historique.

Les rendez-vous durent 30 minutes, du lundi au vendredi de 9 h à 17 h, sous réserve de validation. Un conseiller ne peut pas avoir deux rendez-vous actifs sur le même créneau.

La connexion utilise Active Directory via LDAP chiffré, avec vérification du certificat. Les mots de passe AD ne sont pas stockés dans l’application. Chaque accès à un dossier est contrôlé côté serveur.

Les sessions expirent après inactivité et sont révoquées au départ d’un salarié. Les saisies et formulaires sont protégés ; les requêtes SQL sont préparées. Le code est suivi avec Git, sans mots de passe ni autres secrets.

## 10. Sauvegarde et reprise

Les VM, les fichiers et les bases sont sauvegardés chaque nuit sur le NAS externe, distinct de l’hôte Proxmox. Une sauvegarde adaptée d’Active Directory est également prévue.

Les sauvegardes sont conservées sept jours. Une copie hebdomadaire chiffrée est stockée sur un support externe, déconnecté et conservé hors site.

Je vérifierai les sauvegardes chaque jour ouvré. Avant la présentation au jury, je testerai la restauration d’un fichier, d’une base et d’une VM sur un réseau isolé.

En cas de panne, le réseau et Active Directory seront rétablis avant les fichiers et l’application. Les objectifs à tester sont :

- **RPO :** perdre au maximum 24 heures de données avec les sauvegardes quotidiennes disponibles.
- **RTO :** reprendre sous 4 heures avec un hôte fonctionnel.

Le **RPO** correspond à la quantité maximale de données que l’on accepte de perdre, exprimée en durée. Le **RTO** correspond au délai maximal fixé pour remettre les services en fonctionnement après une panne.

Si seule la copie externe hebdomadaire est disponible, la perte peut atteindre sept jours.

## 11. Assistance et supervision

GLPI centralise l’inventaire et les tickets. Chaque ticket est enregistré, priorisé, traité puis clôturé après vérification.

| Priorité | Exemple | Prise en charge visée |
| --- | --- | --- |
| Critique | Application indisponible pour toute l’agence. | 30 minutes. |
| Haute | Salarié bloqué sans solution de remplacement. | 2 heures. |
| Normale | Demande de matériel ou gêne contournable. | 1 jour ouvré. |

Ces délais sont simulés sur une plage de support du lundi au vendredi, de 9 h à 18 h.

Zabbix surveille les machines, l’espace disque, le DNS et les services web. Une panne doit déclencher une alerte visible.

Les procédures prévoiront le suivi manuel des incidents si GLPI est indisponible et le retour arrière après une modification.

## 12. Budget et licences

Le matériel et les licences de l’établissement seront utilisés en priorité. Les droits d’utilisation devront être vérifiés avant l’installation.

À défaut de licence disponible, Windows Server pourra être utilisé en évaluation officielle, dans les conditions et la durée autorisées. L’évaluation de Windows Server 2025 est limitée à 180 jours.

Le budget dépendra du matériel et des licences disponibles. Une exploitation réelle nécessiterait de chiffrer les licences serveur, les droits de virtualisation, les licences d’accès client et celles des postes.

## 13. Organisation et planning

Je réalise la conception, l’installation, les tests et la documentation. La direction valide les besoins et les accès dans le scénario de l’entreprise. Le jury évalue les livrables et la démonstration.

| Période | Travaux prévus |
| --- | --- |
| Semaine 1 | Besoins, matériel, licences et schéma réseau. |
| Semaine 2 | Proxmox, pfSense, VLAN et AD/DNS/DHCP. |
| Semaine 3 | Partages, groupes, GPO et tests d’accès. |
| Semaine 4 | Site vitrine, application et authentification. |
| Semaine 5 | GLPI, Zabbix, sauvegardes et restauration. |
| Semaine 6 | Corrections, guides et présentation au jury. |

Ce planning sera adapté au temps disponible et au travail nécessaire sur l’application. Les options seront reportées si besoin.

## 14. Compétences et approfondissements

| Domaine | Compétences travaillées |
| --- | --- |
| Support | Inventaire, tickets, documentation et accompagnement. |
| SISR | Virtualisation, Windows Server, réseau, supervision et restauration. |
| Cybersécurité | Droits d’accès, séparation des réseaux, HTTPS et sauvegardes. |

Pour aller plus loin vers un niveau licence ou master, un ou deux sujets pourront être étudiés : analyse de risques, automatisation PowerShell, mesure des temps de reprise ou second contrôleur de domaine.

Ces options ne sont pas nécessaires à la validation du projet. Un second contrôleur sur le même hôte ne protège pas contre la panne de cet hôte et ne rend pas automatiquement le DHCP redondant.

## 15. Livrables et validation finale

Les documents à présenter sont :

1. Le cahier des charges et le schéma réseau.
2. L’inventaire, le plan IP et les règles de filtrage.
3. Les groupes, les droits d’accès et les GPO.
4. Le code, le modèle de données et le guide d’installation.
5. Les procédures d’administration, de sauvegarde et de restauration.
6. Les fiches de tests avec résultats et preuves.
7. Les guides, la charte informatique et le bilan de validation.

Le projet est validé lorsque les tests F01 à F11 réussissent et qu’aucun incident critique ne reste ouvert. Les résultats de restauration et les éventuels écarts sont présentés au jury.

## 16. Points à confirmer

- Matériel, stockage et licences disponibles.
- Version de Windows Server retenue.
- Application fournie, adaptée ou développée.
- Droits d’accès et règles de rendez-vous.
- Temps disponible et publication éventuelle du site.

## Sources

- Cahier des charges initial de Nordéa Banque du 19 octobre 2026.
- [Microsoft — Évaluation de Windows Server 2025](https://www.microsoft.com/en-gb/evalcenter/download-windows-server-2025).
- [Réseau CERTA — Document d’accompagnement du BTS SIO](https://www.reseaucerta.org/wp-content/uploads/sujets/public/GuideAccompagnement30oct.pdf).

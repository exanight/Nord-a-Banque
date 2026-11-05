-- Nordea Banque - Livrable 4
-- Cible : MariaDB 10.11+ ou MySQL 8.0.16+
-- Script de creation uniquement : aucune suppression de donnees.
-- Les dates de rendez-vous sont exprimees en heure locale Europe/Paris.

CREATE DATABASE IF NOT EXISTS nordea_banque
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nordea_banque;

CREATE TABLE UTILISATEUR (
  identifiant_ad VARCHAR(36) NOT NULL COMMENT 'GUID AD stable, jamais le mot de passe',
  nom VARCHAR(80) NOT NULL,
  prenom VARCHAR(80) NOT NULL,
  role ENUM('direction','conseiller_particuliers','conseiller_professionnels',
            'accueil','administration','conformite') NOT NULL,
  actif BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (identifiant_ad),
  CONSTRAINT ck_utilisateur_actif CHECK (actif IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE CLIENT (
  id_client INT UNSIGNED NOT NULL AUTO_INCREMENT,
  type_client ENUM('particulier','professionnel') NOT NULL,
  nom_ou_raison_sociale VARCHAR(150) NOT NULL,
  prenom VARCHAR(80) NULL,
  email VARCHAR(150) NULL,
  telephone VARCHAR(25) NULL,
  conseiller_ad VARCHAR(36) NOT NULL,
  PRIMARY KEY (id_client),
  CONSTRAINT fk_client_conseiller FOREIGN KEY (conseiller_ad)
    REFERENCES UTILISATEUR(identifiant_ad),
  CONSTRAINT ck_client_nom CHECK (CHAR_LENGTH(TRIM(nom_ou_raison_sociale)) > 0),
  CONSTRAINT ck_client_prenom CHECK (
    type_client <> 'particulier'
    OR (prenom IS NOT NULL AND CHAR_LENGTH(TRIM(prenom)) > 0)
  )
) ENGINE=InnoDB;

CREATE TABLE RENDEZ_VOUS (
  id_rdv INT UNSIGNED NOT NULL AUTO_INCREMENT,
  debut DATETIME NOT NULL,
  fin DATETIME NOT NULL,
  objet VARCHAR(150) NOT NULL,
  statut ENUM('prevu','termine','annule') NOT NULL DEFAULT 'prevu',
  id_client INT UNSIGNED NOT NULL,
  conseiller_ad VARCHAR(36) NOT NULL,
  -- Colonne technique calculee, absente du MCD : une annulation libere le creneau.
  creneau_reserve DATETIME GENERATED ALWAYS AS (
    CASE WHEN statut = 'annule' THEN NULL ELSE debut END
  ) STORED,
  PRIMARY KEY (id_rdv),
  CONSTRAINT fk_rdv_client FOREIGN KEY (id_client) REFERENCES CLIENT(id_client),
  CONSTRAINT fk_rdv_conseiller FOREIGN KEY (conseiller_ad)
    REFERENCES UTILISATEUR(identifiant_ad),
  CONSTRAINT uq_rdv_conseiller_creneau UNIQUE (conseiller_ad, creneau_reserve),
  CONSTRAINT ck_rdv_duree CHECK (fin = DATE_ADD(debut, INTERVAL 30 MINUTE)),
  CONSTRAINT ck_rdv_horaires CHECK (
    WEEKDAY(debut) BETWEEN 0 AND 4
    AND DATE(debut) = DATE(fin)
    AND TIME(debut) >= '09:00:00'
    AND TIME(fin) <= '17:00:00'
    AND MINUTE(debut) IN (0,30)
    AND SECOND(debut) = 0
  ),
  CONSTRAINT ck_rdv_objet CHECK (CHAR_LENGTH(TRIM(objet)) > 0)
) ENGINE=InnoDB;

CREATE TABLE DEMANDE (
  id_demande INT UNSIGNED NOT NULL AUTO_INCREMENT,
  objet VARCHAR(150) NOT NULL,
  description TEXT NULL,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_client INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_demande),
  CONSTRAINT fk_demande_client FOREIGN KEY (id_client) REFERENCES CLIENT(id_client),
  CONSTRAINT ck_demande_objet CHECK (CHAR_LENGTH(TRIM(objet)) > 0)
) ENGINE=InnoDB;

CREATE TABLE HISTORIQUE_DEMANDE (
  id_historique INT UNSIGNED NOT NULL AUTO_INCREMENT,
  statut ENUM('nouvelle','en_cours','cloturee') NOT NULL,
  commentaire TEXT NULL,
  date_changement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_demande INT UNSIGNED NOT NULL,
  auteur_ad VARCHAR(36) NOT NULL,
  PRIMARY KEY (id_historique),
  CONSTRAINT fk_historique_demande FOREIGN KEY (id_demande)
    REFERENCES DEMANDE(id_demande),
  CONSTRAINT fk_historique_auteur FOREIGN KEY (auteur_ad)
    REFERENCES UTILISATEUR(identifiant_ad),
  INDEX ix_historique_ordre (id_demande, date_changement, id_historique)
) ENGINE=InnoDB;

-- Verification apres execution : cinq tables doivent apparaitre.
SHOW TABLES;

-- Regles a appliquer dans PHP :
-- 1. Verifier le role conseiller des utilisateurs affectes aux clients et RDV.
-- 2. Creer une demande et son historique initial 'nouvelle' dans UNE transaction.
-- 3. Controler et serialiser les transitions nouvelle -> en_cours -> cloturee.
-- 4. Lire le statut actuel dans le dernier historique (date puis identifiant).
-- 5. Controler chaque acces selon les groupes AD et les dossiers autorises.
-- Hypotheses a valider : horaires 9h-17h et depart a l'heure ou a la demi-heure.

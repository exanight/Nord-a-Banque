-- A appliquer apres 001_schema.sql (ou sur la base deja creee au livrable 4).
-- Aucun acces global implicite pour direction, administration ou conformite.
CREATE TABLE ACCES_DOSSIER (
  identifiant_ad VARCHAR(36) NOT NULL,
  id_client INT UNSIGNED NOT NULL,
  gestion_demandes BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (identifiant_ad,id_client),
  CONSTRAINT fk_acces_utilisateur FOREIGN KEY (identifiant_ad) REFERENCES UTILISATEUR(identifiant_ad),
  CONSTRAINT fk_acces_client FOREIGN KEY (id_client) REFERENCES CLIENT(id_client),
  CONSTRAINT ck_acces_gestion CHECK (gestion_demandes IN (0,1))
) ENGINE=InnoDB;
-- Les autorisations sont ajoutees par le responsable informatique apres validation
-- de la direction, dans la base uniquement. Pas de route publique d'attribution.

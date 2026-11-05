-- Livrable 5 : donnees ENTIEREMENT FICTIVES pour la maquette Nordea.
-- A importer UNE FOIS apres creation_nordea.sql, dans une base de test vide.
-- Les GUID ci-dessous sont inventes : ils ne permettent pas de connexion AD.
-- En cas d'erreur, ne pas continuer ni reimporter : effectuer ROLLBACK
-- dans la meme session ou fermer la connexion, puis examiner l'erreur.
USE nordea_banque;
START TRANSACTION;

-- 15 salaries, repartis selon les effectifs du cahier des charges.
INSERT INTO UTILISATEUR (identifiant_ad,nom,prenom,role,actif) VALUES
('00000000-0000-0000-0000-000000000001','Exemple01','Alice','direction',1),
('00000000-0000-0000-0000-000000000002','Exemple02','Bruno','conseiller_particuliers',1),
('00000000-0000-0000-0000-000000000003','Exemple03','Clara','conseiller_particuliers',1),
('00000000-0000-0000-0000-000000000004','Exemple04','David','conseiller_particuliers',1),
('00000000-0000-0000-0000-000000000005','Exemple05','Emma','conseiller_particuliers',1),
('00000000-0000-0000-0000-000000000006','Exemple06','Farid','conseiller_particuliers',1),
('00000000-0000-0000-0000-000000000007','Exemple07','Gaelle','conseiller_professionnels',1),
('00000000-0000-0000-0000-000000000008','Exemple08','Hugo','conseiller_professionnels',1),
('00000000-0000-0000-0000-000000000009','Exemple09','Ines','conseiller_professionnels',1),
('00000000-0000-0000-0000-000000000010','Exemple10','Jules','accueil',1),
('00000000-0000-0000-0000-000000000011','Exemple11','Karim','accueil',1),
('00000000-0000-0000-0000-000000000012','Exemple12','Lea','administration',1),
('00000000-0000-0000-0000-000000000013','Exemple13','Marc','administration',1),
('00000000-0000-0000-0000-000000000014','Exemple14','Nina','conformite',1),
('00000000-0000-0000-0000-000000000015','Exemple15','Oscar','conformite',1);

-- Courriels reserves aux exemples ; coordonnees facultatives a NULL.
INSERT INTO CLIENT (id_client,type_client,nom_ou_raison_sociale,prenom,email,telephone,conseiller_ad) VALUES
(1001,'particulier','Client-Fictif-A','Camille','client-a@example.test',NULL,'00000000-0000-0000-0000-000000000002'),
(1002,'particulier','Client-Fictif-B','Alex',NULL,NULL,'00000000-0000-0000-0000-000000000002'),
(1003,'particulier','Client-Fictif-C','Lou','client-c@example.test',NULL,'00000000-0000-0000-0000-000000000003'),
(1004,'professionnel','Atelier Fictif Alpha',NULL,'alpha@example.test',NULL,'00000000-0000-0000-0000-000000000007'),
(1005,'professionnel','Entreprise Fictive Beta',NULL,NULL,NULL,'00000000-0000-0000-0000-000000000008'),
(1006,'particulier','Client-Fictif-D','Sacha',NULL,NULL,'00000000-0000-0000-0000-000000000004');

-- 8 rendez-vous : termines, prevus, annules, creneau reutilise, fin a 17h.
-- Un meme horaire peut etre utilise par DEUX conseillers differents.
INSERT INTO RENDEZ_VOUS (id_rdv,debut,fin,objet,statut,id_client,conseiller_ad) VALUES
(2001,'2026-11-05 09:00:00','2026-11-05 09:30:00','Point sur le dossier','termine',1001,'00000000-0000-0000-0000-000000000002'),
(2002,'2026-11-09 09:00:00','2026-11-09 09:30:00','Entretien annule','annule',1001,'00000000-0000-0000-0000-000000000002'),
(2003,'2026-11-09 09:00:00','2026-11-09 09:30:00','Nouveau rendez-vous sur le creneau libere','prevu',1002,'00000000-0000-0000-0000-000000000002'),
(2004,'2026-11-09 09:00:00','2026-11-09 09:30:00','Mise a jour des coordonnees','prevu',1003,'00000000-0000-0000-0000-000000000003'),
(2005,'2026-11-09 09:30:00','2026-11-09 10:00:00','Suivi de demande','prevu',1001,'00000000-0000-0000-0000-000000000002'),
(2006,'2026-11-09 16:30:00','2026-11-09 17:00:00','Suivi entreprise','prevu',1004,'00000000-0000-0000-0000-000000000007'),
(2007,'2026-11-10 10:00:00','2026-11-10 10:30:00','Documents administratifs','prevu',1005,'00000000-0000-0000-0000-000000000008'),
(2008,'2026-11-10 11:00:00','2026-11-10 11:30:00','Entretien reporte','annule',1003,'00000000-0000-0000-0000-000000000003');

-- Client 1001 : plusieurs demandes ; client 1006 : aucune demande ni RDV.
INSERT INTO DEMANDE (id_demande,objet,description,date_creation,id_client) VALUES
(3001,'Attestation','Demande fictive de document administratif.','2026-11-05 09:00:00',1001),
(3002,'Mise a jour des coordonnees',NULL,'2026-11-05 09:30:00',1001),
(3003,'Information sur les horaires','Demande de renseignements sur les horaires.','2026-11-05 10:00:00',1002),
(3004,'Suivi du dossier professionnel','Verification de la reception des documents.','2026-11-05 10:30:00',1004),
(3005,'Renseignement administratif',NULL,'2026-11-05 11:00:00',1005);

INSERT INTO HISTORIQUE_DEMANDE (id_historique,statut,commentaire,date_changement,id_demande,auteur_ad) VALUES
(4001,'nouvelle','Demande enregistree.','2026-11-05 09:00:00',3001,'00000000-0000-0000-0000-000000000010'),
(4002,'en_cours','Preparation du document.','2026-11-05 10:00:00',3001,'00000000-0000-0000-0000-000000000002'),
(4003,'cloturee','Document remis dans la simulation.','2026-11-05 14:00:00',3001,'00000000-0000-0000-0000-000000000002'),
(4004,'nouvelle',NULL,'2026-11-05 09:30:00',3002,'00000000-0000-0000-0000-000000000010'),
(4005,'nouvelle',NULL,'2026-11-05 10:00:00',3003,'00000000-0000-0000-0000-000000000011'),
(4006,'en_cours','Verification en cours.','2026-11-05 11:00:00',3003,'00000000-0000-0000-0000-000000000002'),
(4007,'nouvelle',NULL,'2026-11-05 10:30:00',3004,'00000000-0000-0000-0000-000000000007'),
(4008,'en_cours','Documents en cours de verification.','2026-11-05 12:00:00',3004,'00000000-0000-0000-0000-000000000007'),
(4009,'nouvelle',NULL,'2026-11-05 11:00:00',3005,'00000000-0000-0000-0000-000000000008');

COMMIT;

-- Resultats attendus apres import dans la base vide : 15 / 6 / 8 / 5 / 9.
SELECT 'UTILISATEUR' AS table_nom, COUNT(*) AS nombre FROM UTILISATEUR
UNION ALL SELECT 'CLIENT', COUNT(*) FROM CLIENT
UNION ALL SELECT 'RENDEZ_VOUS', COUNT(*) FROM RENDEZ_VOUS
UNION ALL SELECT 'DEMANDE', COUNT(*) FROM DEMANDE
UNION ALL SELECT 'HISTORIQUE_DEMANDE', COUNT(*) FROM HISTORIQUE_DEMANDE;

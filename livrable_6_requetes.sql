-- Livrable 6 : executer les requetes separement dans phpMyAdmin.
-- Preconditions : creation_nordea.sql puis livrable_5_donnees.sql importes.
-- Les resultats indiques sont ATTENDUS, pas des resultats deja testes.
-- Les exemples SQL ne remplacent pas les controles d'acces de l'application.
USE nordea_banque;

-- 1. SELECT / WHERE / ORDER BY : clients particuliers, tries par nom.
-- Attendu : 4 clients.
SELECT id_client, nom_ou_raison_sociale, prenom, email
FROM CLIENT
WHERE type_client = 'particulier'
ORDER BY nom_ou_raison_sociale, prenom;

-- 2. JOIN : afficher les clients du conseiller Bruno Exemple02.
-- Attendu : clients 1001 et 1002.
SELECT c.id_client, c.nom_ou_raison_sociale, c.prenom,
       u.nom AS nom_conseiller, u.prenom AS prenom_conseiller
FROM CLIENT AS c
JOIN UTILISATEUR AS u ON u.identifiant_ad = c.conseiller_ad
WHERE u.identifiant_ad = '00000000-0000-0000-0000-000000000002'
ORDER BY c.id_client;

-- 3. Agenda du 9 novembre : rendez-vous prevus, avec client et conseiller.
-- Attendu : 4 rendez-vous ; le rendez-vous annule 2002 est exclu.
SELECT r.id_rdv, r.debut, r.fin, r.objet,
       c.nom_ou_raison_sociale AS client, u.prenom AS conseiller
FROM RENDEZ_VOUS AS r
JOIN CLIENT AS c ON c.id_client = r.id_client
JOIN UTILISATEUR AS u ON u.identifiant_ad = r.conseiller_ad
WHERE r.statut = 'prevu'
  AND r.debut >= '2026-11-09 00:00:00'
  AND r.debut < '2026-11-10 00:00:00'
ORDER BY r.debut, r.conseiller_ad;

-- 4. Historique complet de la demande 3001, avec ses auteurs.
-- Attendu : nouvelle -> en_cours -> cloturee, soit 3 lignes.
SELECT h.date_changement, h.statut, h.commentaire, u.nom, u.prenom
FROM HISTORIQUE_DEMANDE AS h
JOIN UTILISATEUR AS u ON u.identifiant_ad = h.auteur_ad
WHERE h.id_demande = 3001
ORDER BY h.date_changement, h.id_historique;

-- 5. Statut actuel : selectionner le dernier evenement de chaque demande.
-- L'identifiant departage deux evenements ayant exactement la meme date.
-- Attendu : 3001 cloturee ; 3002 nouvelle ; 3003 et 3004 en_cours ; 3005 nouvelle.
SELECT d.id_demande, d.objet, h.statut, h.date_changement
FROM DEMANDE AS d
JOIN HISTORIQUE_DEMANDE AS h ON h.id_demande = d.id_demande
WHERE NOT EXISTS (
  SELECT 1 FROM HISTORIQUE_DEMANDE AS suivant
  WHERE suivant.id_demande = h.id_demande
    AND (suivant.date_changement > h.date_changement
      OR (suivant.date_changement = h.date_changement
          AND suivant.id_historique > h.id_historique))
)
ORDER BY d.id_demande;

-- 6. LEFT JOIN / COUNT / GROUP BY : nombre de demandes de chaque client,
-- y compris ceux sans demande. Attendu : 1001 = 2 ; 1003 et 1006 = 0.
SELECT c.id_client, c.nom_ou_raison_sociale, COUNT(d.id_demande) AS nombre_demandes
FROM CLIENT AS c
LEFT JOIN DEMANDE AS d ON d.id_client = c.id_client
GROUP BY c.id_client, c.nom_ou_raison_sociale
ORDER BY nombre_demandes DESC, c.id_client;

-- 7. Effectifs par role : attendu 1 direction, 5 conseillers particuliers,
-- 3 conseillers professionnels, 2 accueil, 2 administration, 2 conformite.
SELECT role, COUNT(*) AS effectif
FROM UTILISATEUR
GROUP BY role
ORDER BY role;

-- 8. SUM / AVG : charge planifiee par conseiller, exprimee en minutes.
-- Attendu : Bruno = 60 minutes ; les trois autres = 30 ; moyenne = 30 par RDV.
SELECT u.identifiant_ad, u.nom, u.prenom,
       COUNT(*) AS nombre_rdv,
       SUM(TIMESTAMPDIFF(MINUTE,r.debut,r.fin)) AS duree_totale_minutes,
       AVG(TIMESTAMPDIFF(MINUTE,r.debut,r.fin)) AS duree_moyenne_minutes
FROM RENDEZ_VOUS AS r
JOIN UTILISATEUR AS u ON u.identifiant_ad = r.conseiller_ad
WHERE r.statut = 'prevu'
GROUP BY u.identifiant_ad, u.nom, u.prenom
ORDER BY duree_totale_minutes DESC, u.identifiant_ad;

-- 9. INSERT / UPDATE / DELETE : exercice sur UN client temporaire,
-- sans toucher aux six clients du jeu. Executer TOUT le bloc ensemble.
-- ROLLBACK annule le bloc (le compteur AUTO_INCREMENT peut avancer).
START TRANSACTION;
INSERT INTO CLIENT (type_client,nom_ou_raison_sociale,prenom,email,conseiller_ad)
VALUES ('particulier','Client-Temporaire-Fictif','Test',NULL,
        '00000000-0000-0000-0000-000000000002');
SET @client_temporaire = LAST_INSERT_ID();

UPDATE CLIENT
SET email = 'temporaire@example.test'
WHERE id_client = @client_temporaire;

-- Attendu : une ligne avec le nouveau courriel.
SELECT id_client, nom_ou_raison_sociale, email
FROM CLIENT WHERE id_client = @client_temporaire;

DELETE FROM CLIENT WHERE id_client = @client_temporaire;
-- Attendu : 0, car le client temporaire vient d'etre supprime.
SELECT COUNT(*) AS client_temporaire_restant
FROM CLIENT WHERE id_client = @client_temporaire;
ROLLBACK;

-- 10. Verification des conflits : attendu AUCUNE ligne.
SELECT conseiller_ad, debut, COUNT(*) AS nombre
FROM RENDEZ_VOUS
WHERE statut <> 'annule'
GROUP BY conseiller_ad, debut
HAVING COUNT(*) > 1;

-- Captures conseillees : agenda (3), historique (4), demandes par client (6).

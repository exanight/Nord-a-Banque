<?php
declare(strict_types=1);
foreach(['Http','Database','Policy','Service'] as $class)require __DIR__.'/../app/src/'.$class.'.php';
$checks=0;
function check(bool $condition,string $message): void{global $checks;if(!$condition)throw new RuntimeException($message);$checks++;}
foreach(['2026-11-07T09:00','2026-02-30T09:00','2026-11-09T09:15','2026-11-09T17:00'] as $value){try{Service::slot($value);throw new RuntimeException('Date acceptee a tort');}catch(HttpError $error){check($error->status===422,'Date invalide');}}
check(Service::slot('2026-11-09T16:30')[1]==='2026-11-09 17:00:00','Dernier creneau');
$db=new Database(['db_dsn'=>'sqlite::memory:','db_user'=>'','db_password'=>'']);
$db->pdo->exec('CREATE TABLE CLIENT (id_client INTEGER PRIMARY KEY,conseiller_ad TEXT); CREATE TABLE ACCES_DOSSIER (id_client INTEGER,identifiant_ad TEXT,gestion_demandes INTEGER); INSERT INTO CLIENT VALUES (1,"a"),(2,"b"); INSERT INTO ACCES_DOSSIER VALUES (2,"admin",1),(1,"dir",0);');
$advisor=new Policy($db,['identifiant_ad'=>'a','role'=>'conseiller_particuliers']);
check((int)$advisor->client(1)['id_client']===1,'Dossier propre');
try{$advisor->client(2);throw new RuntimeException('Acces autre conseiller');}catch(HttpError $error){check($error->status===404,'Autre dossier refuse');}
$admin=new Policy($db,['identifiant_ad'=>'admin','role'=>'administration']);
check((int)$admin->client(2,true)['id_client']===2,'Autorisation administrative explicite');
$dir=new Policy($db,['identifiant_ad'=>'dir','role'=>'direction']);
check((int)$dir->client(1)['id_client']===1,'Direction dossier autorise');
try{$dir->client(2);throw new RuntimeException('Direction acces global');}catch(HttpError $error){check($error->status===404,'Direction limitee');}
$accueil=new Policy($db,['identifiant_ad'=>'acc','role'=>'accueil']);
check(!$accueil->permissions()['requests_read'],'Accueil sans demandes');
try{$accueil->require('requests_write');throw new RuntimeException('Accueil ecrit demandes');}catch(HttpError $error){check($error->status===403,'Demandes refusees');}
check($accueil->permissions()['appointments_write'],'Accueil gere RDV');
$conformite=new Policy($db,['identifiant_ad'=>'con','role'=>'conformite']);
check(!$conformite->permissions()['requests_write'],'Conformite lecture seulement');
echo "$checks verifications PHP reussies (SQLite pour les tests de droits).\n";

<?php
declare(strict_types=1);
final class Service {
    public function __construct(private Database $db,private Policy $policy) {}
    public function bootstrap(): array {
        [$scope,$params]=$this->policy->scope();
        $clients=$this->db->rows('SELECT c.* FROM CLIENT c WHERE '.$scope.' ORDER BY c.nom_ou_raison_sociale',$params);
        $appointments=$this->db->rows('SELECT r.id_rdv,r.id_client,r.conseiller_ad,r.debut,r.fin,r.objet,r.statut FROM RENDEZ_VOUS r JOIN CLIENT c ON c.id_client=r.id_client WHERE '.$scope.' ORDER BY r.debut',$params);
        $can=$this->policy->permissions();$requests=[];$history=[];
        if($can['requests_read']){
            $requests=$this->db->rows("SELECT d.*,h.statut FROM DEMANDE d JOIN CLIENT c ON c.id_client=d.id_client JOIN HISTORIQUE_DEMANDE h ON h.id_historique=(SELECT x.id_historique FROM HISTORIQUE_DEMANDE x WHERE x.id_demande=d.id_demande ORDER BY x.date_changement DESC,x.id_historique DESC LIMIT 1) WHERE $scope ORDER BY d.date_creation DESC",$params);
            $history=$this->db->rows("SELECT h.*,CONCAT(u.prenom,' ',u.nom) AS auteur_nom FROM HISTORIQUE_DEMANDE h JOIN DEMANDE d ON d.id_demande=h.id_demande JOIN CLIENT c ON c.id_client=d.id_client JOIN UTILISATEUR u ON u.identifiant_ad=h.auteur_ad WHERE $scope ORDER BY h.date_changement,h.id_historique",$params);
        }
        // Annuaire de travail limite aux noms et identifiants des conseillers.
        $advisors=$this->db->rows("SELECT identifiant_ad,nom,prenom FROM UTILISATEUR WHERE actif=1 AND role IN ('conseiller_particuliers','conseiller_professionnels') ORDER BY nom");
        if($this->policy->advisor())$advisors=array_values(array_filter($advisors,fn($u)=>$u['identifiant_ad']===$this->policy->user['identifiant_ad']));
        return ['user'=>$this->policy->user,'can'=>$can,'clients'=>$clients,'advisors'=>$advisors,'appointments'=>$appointments,'requests'=>$requests,'history'=>$history];
    }
    private function advisor(string $guid): void {
        if($this->policy->advisor()&&$guid!==$this->policy->user['identifiant_ad'])throw new HttpError(403,'Vous ne pouvez affecter que vos propres dossiers.');
        if(!$this->db->one("SELECT 1 FROM UTILISATEUR WHERE identifiant_ad=? AND actif=1 AND role IN ('conseiller_particuliers','conseiller_professionnels')",[$guid]))throw new HttpError(422,'Conseiller invalide.');
    }
    public function read(string $resource,?int $id=null): array {
        $data=$this->bootstrap();
        $mapping=['clients'=>['clients','id_client'],'appointments'=>['appointments','id_rdv'],'requests'=>['requests','id_demande']];
        if(!isset($mapping[$resource]))throw new HttpError(404,'Ressource introuvable.');
        if($resource==='requests')$this->policy->require('requests_read');
        [$key,$field]=$mapping[$resource];
        if(!$id)return $data[$key];
        foreach($data[$key] as $row)if((int)$row[$field]===$id)return $row;
        throw new HttpError(404,'Dossier introuvable ou non autorisé.');
    }
    public function client(array $body,?int $id): array {
        $this->policy->require('clients_write');if($id)$this->policy->client($id);
        $type=Http::text($body,'type_client',20);
        if(!in_array($type,['particulier','professionnel'],true))throw new HttpError(422,'Type de client invalide.');
        $name=Http::text($body,'nom_ou_raison_sociale',150);$first=Http::text($body,'prenom',80,$type==='particulier');
        $email=Http::text($body,'email',150,false);$phone=Http::text($body,'telephone',25,false);
        if($email&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new HttpError(422,'Courriel invalide.');
        $advisor=Http::text($body,'conseiller_ad',36);$this->advisor($advisor);
        $params=[$type,$name,$first,$email,$phone,$advisor];
        if($id)$this->db->execute('UPDATE CLIENT SET type_client=?,nom_ou_raison_sociale=?,prenom=?,email=?,telephone=?,conseiller_ad=? WHERE id_client=?',[...$params,$id]);
        else {$this->db->execute('INSERT INTO CLIENT (type_client,nom_ou_raison_sociale,prenom,email,telephone,conseiller_ad) VALUES (?,?,?,?,?,?)',$params);$id=(int)$this->db->pdo->lastInsertId();}
        return ['id_client'=>$id];
    }
    public static function slot(string $value): array {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new DateTimeZone('Europe/Paris'));
        if(!$date||$date->format('Y-m-d\TH:i')!==$value||(int)$date->format('Y')<1000||(int)$date->format('N')>5||(int)$date->format('H')<9||(int)$date->format('H')>16||!in_array($date->format('i'),['00','30'],true))throw new HttpError(422,'Créneau invalide : lundi à vendredi, toutes les 30 minutes de 9 h à 16 h 30.');
        return [$date->format('Y-m-d H:i:s'),$date->modify('+30 minutes')->format('Y-m-d H:i:s')];
    }
    public function appointment(array $body,?int $id): array {
        $this->policy->require('appointments_write');
        return $this->db->transaction(function()use($body,$id){
            $existing=null;
            if($id){$existing=$this->db->one('SELECT * FROM RENDEZ_VOUS WHERE id_rdv=? FOR UPDATE',[$id]);if(!$existing)throw new HttpError(404,'Rendez-vous introuvable.');$this->policy->client((int)$existing['id_client']);if($existing['statut']!=='prevu')throw new HttpError(409,'Ce rendez-vous ne peut plus être modifié.');}
            if(($body['statut']??null)==='annule'&&$id){$this->db->execute("UPDATE RENDEZ_VOUS SET statut='annule' WHERE id_rdv=?",[$id]);return ['id_rdv'=>$id,'statut'=>'annule'];}
            $client=Http::id($body['id_client']??null);$this->policy->client($client);
            $advisor=Http::text($body,'conseiller_ad',36);$this->advisor($advisor);
            [$start,$end]=self::slot(Http::text($body,'debut',16));$subject=Http::text($body,'objet',150);
            // La contrainte UNIQUE de la base arbitre aussi les saisies simultanees.
            if($id)$this->db->execute('UPDATE RENDEZ_VOUS SET id_client=?,conseiller_ad=?,debut=?,fin=?,objet=? WHERE id_rdv=?',[$client,$advisor,$start,$end,$subject,$id]);
            else {$this->db->execute("INSERT INTO RENDEZ_VOUS (id_client,conseiller_ad,debut,fin,objet,statut) VALUES (?,?,?,?,?,'prevu')",[$client,$advisor,$start,$end,$subject]);$id=(int)$this->db->pdo->lastInsertId();}
            return ['id_rdv'=>$id];
        });
    }
    public function request(array $body): array {
        $this->policy->require('requests_write');$client=Http::id($body['id_client']??null);$this->policy->client($client,true);
        $subject=Http::text($body,'objet',150);$description=Http::text($body,'description',4000,false);
        return $this->db->transaction(function()use($client,$subject,$description){
            $this->db->execute('INSERT INTO DEMANDE (id_client,objet,description) VALUES (?,?,?)',[$client,$subject,$description]);$id=(int)$this->db->pdo->lastInsertId();
            $this->db->execute("INSERT INTO HISTORIQUE_DEMANDE (id_demande,statut,auteur_ad) VALUES (?,'nouvelle',?)",[$id,$this->policy->user['identifiant_ad']]);return ['id_demande'=>$id];
        });
    }
    public function transition(int $id,array $body): array {
        $this->policy->require('requests_write');$next=Http::text($body,'statut',20);$comment=Http::text($body,'commentaire',4000,false);
        return $this->db->transaction(function()use($id,$next,$comment){
            // Verrouiller la demande serialise les changements concurrents.
            $request=$this->db->one('SELECT * FROM DEMANDE WHERE id_demande=? FOR UPDATE',[$id]);
            if(!$request)throw new HttpError(404,'Demande introuvable.');$this->policy->client((int)$request['id_client'],true);
            $last=$this->db->one('SELECT statut FROM HISTORIQUE_DEMANDE WHERE id_demande=? ORDER BY date_changement DESC,id_historique DESC LIMIT 1',[$id]);
            $expected=['nouvelle'=>'en_cours','en_cours'=>'cloturee'][$last['statut']??'']??null;
            if($expected!==$next)throw new HttpError(409,'Transition impossible. Rechargez la demande.');
            $this->db->execute('INSERT INTO HISTORIQUE_DEMANDE (id_demande,statut,commentaire,auteur_ad) VALUES (?,?,?,?)',[$id,$next,$comment,$this->policy->user['identifiant_ad']]);return ['id_demande'=>$id,'statut'=>$next];
        });
    }
}

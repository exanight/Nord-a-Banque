<?php
declare(strict_types=1);
final class Policy {
    public function __construct(private Database $db,public readonly array $user) {}
    public function advisor(): bool{return in_array($this->user['role'],['conseiller_particuliers','conseiller_professionnels'],true);}
    public function permissions(): array {return ['clients_write'=>$this->advisor()||$this->user['role']==='accueil','appointments_write'=>$this->advisor()||$this->user['role']==='accueil','requests_read'=>$this->user['role']!=='accueil','requests_write'=>$this->advisor()||$this->user['role']==='administration'];}
    public function scope(string $alias='c'): array {
        if($this->user['role']==='accueil')return ['1=1',[]];
        if($this->advisor())return [$alias.'.conseiller_ad=?',[$this->user['identifiant_ad']]];
        return ['EXISTS (SELECT 1 FROM ACCES_DOSSIER a WHERE a.id_client='.$alias.'.id_client AND a.identifiant_ad=?)',[$this->user['identifiant_ad']]];
    }
    public function client(int $id,bool $writeRequest=false): array {
        [$scope,$params]=$this->scope();
        $row=$this->db->one('SELECT c.* FROM CLIENT c WHERE c.id_client=? AND '.$scope,[$id,...$params]);
        if(!$row)throw new HttpError(404,'Dossier introuvable ou non autorisé.');
        if($writeRequest&&(!$this->permissions()['requests_write']||(!$this->advisor()&&!$this->db->one('SELECT 1 FROM ACCES_DOSSIER WHERE id_client=? AND identifiant_ad=? AND gestion_demandes=1',[$id,$this->user['identifiant_ad']]))))throw new HttpError(403,'Modification de cette demande non autorisée.');
        return $row;
    }
    public function require(string $permission): void {if(!($this->permissions()[$permission]??false))throw new HttpError(403,'Action non autorisée pour votre profil.');}
}

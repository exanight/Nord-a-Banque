<?php
declare(strict_types=1);
final class Directory {
    public function __construct(private array $config) {}
    private function connect(): LDAP\Connection {
        if (!extension_loaded('ldap') || !str_starts_with($this->config['ldap_url'], 'ldaps://') || !is_file($this->config['ldap_ca'])) throw new HttpError(503, 'L’annuaire sécurisé n’est pas configuré.');
        ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);
        ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, $this->config['ldap_ca']);
        $connection=ldap_connect($this->config['ldap_url']);
        if (!$connection) throw new HttpError(503, 'Annuaire indisponible.');
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, 5);
        return $connection;
    }
    private function service(): LDAP\Connection {
        if (!$this->config['ldap_bind_dn'] || !$this->config['ldap_bind_password']) throw new HttpError(503, 'Le compte de lecture de l’annuaire n’est pas configuré.');
        $connection=$this->connect();
        if (!@ldap_bind($connection,$this->config['ldap_bind_dn'],$this->config['ldap_bind_password'])) {ldap_unbind($connection);throw new HttpError(503,'Annuaire indisponible.');}
        return $connection;
    }
    private function find(string $filter): array {
        $connection=$this->service();
        try {
            $result=@ldap_search($connection,$this->config['ldap_base'],'(&(objectCategory=person)(objectClass=user)'.$filter.')',['objectGUID','givenName','sn','memberOf','userAccountControl','pwdLastSet','accountExpires'],0,2,5);
            if (!$result) throw new HttpError(503,'Impossible de vérifier le compte dans l’annuaire.');
            $entries=ldap_get_entries($connection,$result);
            if ($entries['count']!==1) throw new HttpError(401,'Identifiants invalides ou compte non autorisé.');
            return $entries[0];
        } finally { ldap_unbind($connection); }
    }
    private function identity(array $entry): array {
        $uac=(int)($entry['useraccountcontrol'][0]??2);
        $expires=(string)($entry['accountexpires'][0]??'0');
        if (($uac & 2)!==0 || ($expires!=='0'&&$expires!=='9223372036854775807'&&(float)$expires/10000000-11644473600<=time())) throw new HttpError(401,'Compte désactivé ou expiré.');
        $groups=array_map('strtolower',array_filter($entry['memberof']??[], 'is_string'));
        $roles=[];
        foreach($this->config['groups'] as $role=>$dn)if(in_array(strtolower($dn),$groups,true))$roles[]=$role;
        if(count($roles)!==1)throw new HttpError(403,'Votre groupe métier doit être validé par le responsable informatique.');
        $bytes=$entry['objectguid'][0]??'';
        if(strlen($bytes)!==16)throw new HttpError(503,'Identifiant annuaire invalide.');
        $parts=unpack('Vfirst/vsecond/vthird/H4fourth/H12fifth',$bytes);
        $guid=sprintf('%08x-%04x-%04x-%s-%s',$parts['first'],$parts['second'],$parts['third'],$parts['fourth'],$parts['fifth']);
        return ['identifiant_ad'=>$guid,'nom'=>$entry['sn'][0]??'','prenom'=>$entry['givenname'][0]??'','role'=>$roles[0],'password_version'=>(string)($entry['pwdlastset'][0]??'')];
    }
    public function login(string $username,string $password): array {
        if(!preg_match('/^[a-zA-Z0-9._-]{1,80}$/D',$username)||$password==='')throw new HttpError(401,'Identifiants invalides ou compte non autorisé.');
        $entry=$this->find('(sAMAccountName='.ldap_escape($username,'',LDAP_ESCAPE_FILTER).')');
        $identity=$this->identity($entry);
        $connection=$this->connect();
        try {if(!@ldap_bind($connection,$entry['dn'],$password))throw new HttpError(401,'Identifiants invalides ou compte non autorisé.');}
        finally {ldap_unbind($connection);}
        return $identity;
    }
    public function refresh(string $guid): array {
        if(!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D',$guid))throw new HttpError(401,'Session invalide.');
        $hex=str_replace('-','',$guid);
        $binary=pack('Vvv',hexdec(substr($hex,0,8)),hexdec(substr($hex,8,4)),hexdec(substr($hex,12,4))).hex2bin(substr($hex,16));
        $filter='';foreach(str_split($binary) as $byte)$filter.='\\'.bin2hex($byte);
        return $this->identity($this->find('(objectGUID='.$filter.')'));
    }
}

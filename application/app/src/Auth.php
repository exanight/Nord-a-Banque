<?php
declare(strict_types=1);
final class Auth {
    public function __construct(private Database $db,private Directory $directory,private array $config) {}
    public static function csrf(): string {return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
    public static function checkCsrf(): void {
        if(!hash_equals(self::csrf(),$_SERVER['HTTP_X_CSRF_TOKEN']??''))throw new HttpError(403,'Session du formulaire expirée. Rechargez la page.');
    }
    public static function logout(): void {
        $_SESSION=[];
        setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
        session_destroy();
    }
    private function throttle(string $username): void {
        $folder=dirname(__DIR__,2).'/var';
        if(!is_dir($folder)||!is_writable($folder))throw new HttpError(503,'Le contrôle des tentatives n’est pas disponible.');
        // Compteurs partages, verrouilles, sans identifiant en clair.
        foreach(['ip:'.($_SERVER['REMOTE_ADDR']??''),'account:'.strtolower($username)] as $key){
            $file=fopen($folder.'/login-'.hash('sha256',$key).'.json','c+');
            if(!$file||!flock($file,LOCK_EX))throw new HttpError(503,'Connexion temporairement indisponible.');
            try{$counter=json_decode(stream_get_contents($file),true)?:['start'=>time(),'count'=>0];if(time()-$counter['start']>900)$counter=['start'=>time(),'count'=>0];
                if($counter['count']>=10)throw new HttpError(429,'Trop de tentatives. Réessayez dans quinze minutes.');
                $counter['count']++;rewind($file);ftruncate($file,0);fwrite($file,json_encode($counter));fflush($file);
            }finally{flock($file,LOCK_UN);fclose($file);}
        }
    }
    public function login(array $body): array {
        $username=Http::text($body,'username',80);$password=Http::text($body,'password',256);
        // Conserver les espaces eventuels du mot de passe ; Http::text ne sert ici qu'a valider.
        $password=$body['password'];
        $this->throttle($username);
        $identity=$this->directory->login($username,$password);
        $this->db->execute('INSERT INTO UTILISATEUR (identifiant_ad,nom,prenom,role,actif) VALUES (?,?,?,?,1) ON DUPLICATE KEY UPDATE nom=VALUES(nom),prenom=VALUES(prenom),role=VALUES(role)',[$identity['identifiant_ad'],$identity['nom'],$identity['prenom'],$identity['role']]);
        $user=$this->db->one('SELECT * FROM UTILISATEUR WHERE identifiant_ad=?',[$identity['identifiant_ad']]);
        if(!$user||!(bool)$user['actif'])throw new HttpError(403,'Compte non autorisé.');
        session_regenerate_id(true);
        $_SESSION=['guid'=>$identity['identifiant_ad'],'password_version'=>$identity['password_version'],'start'=>time(),'last'=>time(),'csrf'=>bin2hex(random_bytes(32))];
        unset($user['actif']);return ['user'=>$user,'csrf'=>self::csrf()];
    }
    public function user(): array {
        if(empty($_SESSION['guid']))throw new HttpError(401,'Veuillez vous connecter.');
        if(time()-($_SESSION['last']??0)>$this->config['session_idle_seconds']||time()-($_SESSION['start']??0)>$this->config['session_max_seconds']){self::logout();throw new HttpError(401,'Votre session a expiré.');}
        try{$identity=$this->directory->refresh($_SESSION['guid']);}
        catch(HttpError $error){if(in_array($error->status,[401,403],true))self::logout();throw $error;}
        if(!hash_equals($_SESSION['password_version'],$identity['password_version'])){self::logout();throw new HttpError(401,'Veuillez vous reconnecter.');}
        $user=$this->db->one('SELECT identifiant_ad,nom,prenom,role,actif FROM UTILISATEUR WHERE identifiant_ad=?',[$_SESSION['guid']]);
        if(!$user||!(bool)$user['actif']){self::logout();throw new HttpError(401,'Compte désactivé.');}
        $user['role']=$identity['role'];$_SESSION['last']=time();unset($user['actif']);return $user;
    }
}

<?php

/**
 * Description of Auth
 *
 * @author 
 */
class Auth {
    use tSingleton;
    const COOKIENAME = 'authbypass';
    
    protected function __construct(){
        session_start();
        if(isset($_COOKIE[self::COOKIENAME])) {
            $this->log($_COOKIE[self::COOKIENAME]);
        }
    }
    
    public function subscribe($login, $pwd) {
        global $db;
        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $q = 'insert into users values(null, "'.addslashes($login).'", "'.$hash.'", 0)';
        $db->query($q);
    }
    
    public function tryLog($login, $pwd): bool {
        global $db;

        // Récupération de l'utilisateur par login
        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pwd, $user['pwd'])) {
            $this->log($user['id']);
            return true;
        } else {
            return false;
        }
    }

    public function log($id) {
        $_SESSION['userid'] = $id;
    }
    
    public function logoff() {
        $_SESSION['userid'] = null;
    }
    
    public function isLogged() {
        return !empty($_SESSION['userid']);
    }
    
    public function getSid() {
        return session_id();
    }
    
    public function getCodeFromLogin($login) {
        global $db;
        $q = 'select id, pwd from users where login="'.$login.'"';
        return $db->query($q)->fetch(PDO::FETCH_ASSOC);
    }
    
    public function resetPwd($id, $code, $newPwd) {
        global $db;
        $hash = password_hash($newPwd, PASSWORD_BCRYPT);
        $q = 'update users set pwd="'.$hash.'" where id="'.$id.'" and pwd="'.$code.'"';
        $db->query($q);
    }
}

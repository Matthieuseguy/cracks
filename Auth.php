<?php

/**
 * Description of Auth
 *
 * @author 
 */
class Auth {
    use tSingleton;
    
    public function subscribe($login, $pwd) {
        global $db;
        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "INSERT INTO users (login, pwd, isadmin) VALUES (?, ?, 0)"
        );
        $stmt->execute([$login, $hash]);
    }
    
    public function tryLog($login, $pwd): bool {
        global $db;

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
        $stmt = $db->prepare(
            "SELECT id, pwd FROM users WHERE login = ?"
        );
        $stmt->execute([$login]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function resetPwd($id, $code, $newPwd) {
        global $db;
        $hash = password_hash($newPwd, PASSWORD_BCRYPT);

        $stmt = $db->prepare(
            "UPDATE users SET pwd = ? WHERE id = ? AND pwd = ?"
        );
        $stmt->execute([$hash, $id, $code]);
    }
}

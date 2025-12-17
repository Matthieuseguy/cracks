<?php

/**
 * Description of Auth
 *
 * @author 
 */
class Auth {
    use tSingleton;
    
    // Remédiation : ajout des variables nombre de tentatives et durée de blocage
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 15;
    
    public function subscribe($login, $pwd) {
        global $db;
        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "INSERT INTO users (login, pwd, isadmin, failed_attempts) VALUES (?, ?, 0, 0)"
        );
        $stmt->execute([$login, $hash]);
    }
    
    public function tryLog($login, $pwd): bool {
        global $db;

        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        //Remédiation : vérifier si le compte est verrouillé directement depuis la base de données
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return false;
        }

        if (password_verify($pwd, $user['pwd'])) {
            //Mot de passe correct : réinitialiser les tentatives
            $stmt = $db->prepare(
                "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?"
            );
            $stmt->execute([$user['id']]);
            
            $this->log($user['id']);
            return true;
        } else {
            //Mot de passe incorrect : tentatives +1
            $newAttempts = $user['failed_attempts'] + 1;
            
            if ($newAttempts >= self::MAX_ATTEMPTS) {
                //Blocage du compte en cs de tentatives max atteintes: 
                $lockUntil = date('Y-m-d H:i:s', time() + (self::LOCKOUT_TIME * 60));
                $stmt = $db->prepare(
                    "UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?"
                );
                $stmt->execute([$newAttempts, $lockUntil, $user['id']]);
            } else {
                $stmt = $db->prepare(
                    "UPDATE users SET failed_attempts = ? WHERE id = ?"
                );
                $stmt->execute([$newAttempts, $user['id']]);
            }
            
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
            "UPDATE users SET pwd = ?, failed_attempts = 0, locked_until = NULL WHERE id = ? AND pwd = ?"
        );
        $stmt->execute([$hash, $id, $code]);
    }
}
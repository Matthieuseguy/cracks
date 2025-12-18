<?php

/**
 * Auth – gestion d'authentification sécurisée
 */
class Auth {
    use tSingleton;

    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 15; // minutes

    private function ensureSession(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function subscribe(string $login, string $pwd): void {
        global $db;

        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "INSERT INTO users (login, pwd, isadmin, failed_attempts, locked_until)
             VALUES (?, ?, 0, 0, NULL)"
        );
        $stmt->execute([$login, $hash]);
    }


    public function tryLog(string $login, string $pwd): bool {
        global $db;
        $this->ensureSession();

        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            echo '<p style="color: red;">Vous avez effectué trop de tentatives de connexion,</p>';
            echo '<p style="color: red;">votre compte est momentanément bloqué</p>';
            return false;
        }

        // Mot de passe correct
        if (password_verify($pwd, $user['pwd'])) {

            // Régénération du SID (anti fixation)
            session_regenerate_id(true);

            // Reset tentatives
            $stmt = $db->prepare(
                "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?"
            );
            $stmt->execute([$user['id']]);

            // Login effectif
            $_SESSION['userid'] = (int)$user['id'];
            $_SESSION['logged_at'] = time();

            return true;
        }

        // Mot de passe incorrect
        $newAttempts = ((int)$user['failed_attempts']) + 1;

        if ($newAttempts >= self::MAX_ATTEMPTS) {
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


    public function logoff(): void {
        $this->ensureSession();

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }


    public function isLogged(): bool {
        $this->ensureSession();
        return !empty($_SESSION['userid']);
    }


    public function getSid(): string {
        $this->ensureSession();
        return session_id();
    }

    public function getCodeFromLogin(string $login): ?array {
        global $db;

        $stmt = $db->prepare(
            "SELECT id, pwd FROM users WHERE login = ?"
        );
        $stmt->execute([$login]);

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    
    public function resetPwd(int $id, string $code, string $newPwd): bool {
        global $db;

        $hash = password_hash($newPwd, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "UPDATE users
             SET pwd = ?, failed_attempts = 0, locked_until = NULL
             WHERE id = ? AND pwd = ?"
        );
        $stmt->execute([$hash, $id, $code]);

        return $stmt->rowCount() === 1;
    }
}

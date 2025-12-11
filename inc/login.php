<?php
    if(!empty($_REQUEST['go'])) {
        $login = trim($_REQUEST['login'] ?? '');
        $pwd = $_REQUEST['pwd'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $auth = Auth::getInstance();

        $locked = $auth->isLocked($login, $ip);
        if(!empty($locked)) {
            $mins = ceil(intval($locked) / 60);
            echo '<p>Trop de tentatives de connexion. Réessayez dans '.htmlspecialchars($mins).' minute(s).</p>';
        } else {
            if($auth->tryLog($login, $pwd)) {
                $auth->resetAttempts($login, $ip);
                header('Location:index.php?sid='. $auth->getSid());
                exit;
            } else {
                $auth->recordFailedAttempt($login, $ip);
                $remaining = $auth->attemptsRemaining($login, $ip);
                echo '<p>Erreur d\'identifiants !';
                if($remaining > 0) {
                    echo ' Il vous reste '.intval($remaining).' tentative(s).';
                } else {
                    echo ' Compte bloqué pour '.ceil(Auth::LOCKOUT_SECONDS/60).' minute(s).';
                }
                echo '</p>';
            }
        }
    }
?><form method="post">
    <div>
        <h2>Connexion</h2>
        <p>
            <label>
                Login
                <input type="text"
                       required="required"
                       name="login" />
            </label>
        </p>
        <p>
            <label>
                Mot de passe
                <input type="password"
                       required="required"
                       name="pwd" />
            </label>
        </p>
        <input type="submit" name="go" value="Se connecter" />
    </div>
</form>
<p>
    Mot de passe oublié ?
    Envoyez un mail à
    <a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo ADMIN_EMAIL; ?></a>
    avec votre login
    pour recevoir un lien
    de reset de mot de passe !
</p>
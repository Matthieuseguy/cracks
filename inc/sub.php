<?php
    if(!empty($_REQUEST['valid'])) {
        $login = preg_replace('/\s+/', '', $_REQUEST['login']);
        $pwd   = preg_replace('/\s+/', '', $_REQUEST['pwd']);

        Auth::getInstance()->subscribe($login, $pwd);
        echo '<p>Inscription réalisée avec succès !</p>';
    }
?><form method="post">
    <div>
        <h2>Inscription</h2>
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
        <input type="submit" name="valid" value="Valider l'inscription" />
    </div>
</form>

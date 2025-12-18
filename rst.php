<?php

require_once 'config.php';

if($systemMdp != $_REQUEST['mdp']) {
    echo 'Accès interdit !';
    exit;
}

$stmt = $db->prepare("SELECT isadmin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['userid']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['isadmin'] != 1) {
    echo 'Accès interdit !';
    exit;
}

?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Cracks are forming</title>
        <script type="text/javascript" src="script.js"></script>
    </head>
    <body>
        <h1>Création code reset</h1>
        <form method="post">
            <p>
                <input type="text" name="login" placeholder="login" />
                <input type="submit" name="valid" value="Obtenir le lien" />
            </p>
        </form>
        <p>Envoyer ce code à l'utilisateur qui a oublié son mdp</p>
        <?php if(!empty($_REQUEST['valid'])) {
            $login = htmlspecialchars($_REQUEST['login'], ENT_QUOTES, 'UTF-8');
            $found = Auth::getInstance()->getCodeFromLogin($login);
            if (!$found) {
                echo '<p style="color: red;">Error.</p>';
            } else {
                //Creation token sécurisé si l'utilisateur existe
                $token = hash('sha256', $found['id'] . $found['pwd'] . 'kf73HT4F0384YGF');
                $resetLink = $_SERVER['HTTP_HOST'] . '/?inc=rst&id=' . $found['id'] . '&code=' . $token;
                ?>
        <kbd><?php echo htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'); ?></kbd>
        <p style="color: green;">Lien généré pour : <strong><?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?></strong></p>
        <?php 
            }
        }
        ?>
    </body>
</html>
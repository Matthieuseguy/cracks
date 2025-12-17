<?php
    if (!Auth::getInstance()->isLogged()) {
        header('Location: index.php?inc=login&error=auth_required');
        exit;
    }

    if(!empty($_REQUEST['val'])) {
        $char_content = htmlspecialchars($_REQUEST['content'], ENT_QUOTES, 'UTF-8');
        $q = 'insert into cracks (content, owner, datesend) '
                . ' values("'.nl2br($char_content).'", "'.$_SESSION['userid'].'", '.time().')';
        $db->query($q);
        // rediriger vers le nouveau crack
        header('Location:index.php?inc=search&cid='.$db->lastInsertId());
        exit;
    }
?><form method="post">
    <div>
        <h2>Ajouter un crack</h2>
        <p>
            <label for="content">
                Contenu
            </label>
            <textarea name="content"
                      id="content"
                      required="required"></textarea>
            <input type="submit" name="val" value="Ajouter ce crack" />
        </p>
    </div>
</form>
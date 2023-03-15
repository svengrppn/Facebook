<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Supprimer un post</title>
  </head>
  <body>
    <?php
    session_start();
      try {
        $pdo = new PDO('mysql:host=localhost;dbname=facebook;charset=utf8', 'root', 'Super');
      } catch (PDOException $e) {
        echo 'Connexion échouée : ' . $e->getMessage();
        exit();
      }
      $_SESSION['supprime'] = false;
      if (isset($_POST['supprimer'])) {
        // Récupérer l'identifiant du post à supprimer
        $idPost = filter_input(INPUT_POST,'idPost',FILTER_SANITIZE_NUMBER_INT);
      
        // Début de la transaction
        $pdo->beginTransaction();
      
        try {
            // Sélectionner les noms de fichiers des médias à supprimer
            $stmt = $pdo->prepare('SELECT nomFichierMedia FROM media WHERE idPost = :idPost');
            $stmt->bindParam(':idPost', $idPost, PDO::PARAM_INT);
            $stmt->execute();
            $medias = $stmt->fetchAll(PDO::FETCH_ASSOC);
          
            // Supprimer les fichiers médias du dossier "uploads"
            foreach ($medias as $media) {
              $chemin = 'uploads/' . $media['nomFichierMedia'];
              if (file_exists($chemin)) {
                unlink($chemin);
              }
            }
          
            // Supprimer les enregistrements des médias dans la base de données
            $stmt = $pdo->prepare('DELETE FROM media WHERE idPost = :idPost');
            $stmt->bindParam(':idPost', $idPost, PDO::PARAM_INT);
            $stmt->execute();
          
            // Supprimer l'enregistrement du post dans la base de données
            $stmt = $pdo->prepare('DELETE FROM post WHERE idPost = :idPost');
            $stmt->bindParam(':idPost', $idPost, PDO::PARAM_INT);
            $stmt->execute();
          
            // Valider la transaction
            $pdo->commit();
          
            // Afficher un message de confirmation
            $_SESSION['supprime'] = true;
          
            // Supprimer la variable de session
            unset($_SESSION['valid']);
          
            // Rediriger vers la page d'accueil
            header('Location: index.php');
            exit;
          
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollBack();
        }
      }
    ?>
  </body>
</html>
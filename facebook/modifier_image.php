<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idMedia = $_POST['idMedia'];
    $commentaire = $_POST['commentaire'];
    
    $stmt = $pdo->prepare('UPDATE post INNER JOIN media ON post.idPost = media.idPost SET post.commentaire = :commentaire WHERE media.idMedia = :idMedia');
    $stmt->bindParam(':commentaire', $commentaire);
    $stmt->bindParam(':idMedia', $idMedia);
    $stmt->execute();
    
    // Rediriger l'utilisateur vers la page index.php après la mise à jour
    header('Location: index.php');
    exit();
} else {
    $idMedia = $_GET['id'];
    
    $stmt = $pdo->prepare('SELECT media.nomFichierMedia, post.commentaire FROM media INNER JOIN post ON media.idPost = post.idPost WHERE media.idMedia = :idMedia');
    $stmt->bindParam(':idMedia', $idMedia);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nomFichierMedia = $result['nomFichierMedia'];
    $commentaire = $result['commentaire'];
    
    echo '<h1>Modifier l\'image "' . $nomFichierMedia . '"</h1>';
    echo '<form method="POST">';
    echo '<input type="hidden" name="idMedia" value="' . $idMedia . '">';
    echo '<label for="commentaire">Commentaire :</label><br>';
    echo '<input type="text" name="commentaire" value="' . $commentaire . '"><br><br>';
    echo '<input type="submit" value="Enregistrer les modifications">';
    echo '</form>';
}
 ?>
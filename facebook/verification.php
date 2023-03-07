
<?php
session_start();
// Connexion à la base de données
try {
  $pdo = new PDO('mysql:host=localhost;dbname=facebook', 'root', 'Super');
} catch (PDOException $e) {
  echo 'Connexion échouée : ' . $e->getMessage();
  exit();
}

$errors = array();
$maxFileSize = 3 * 1024 * 1024; // 3 Méga-octets
$maxTotalSize = 70 * 1024 * 1024; // 70 Méga-octets
$images = $_FILES['images'];
$directory = "img_uploads/";
$text = $_POST['text'];
if(!is_dir($directory)){
  mkdir($directory);
}
if (isset($images)) {
  $totalSize = 0;
  $validImages = array();
  for ($i = 0; $i < count($images['name']); $i++) {
    
    $imageInfo = getimagesize($images['tmp_name'][$i]);
    // Vérification de l'extension
    $extension = strtolower(pathinfo($images['name'][$i], PATHINFO_EXTENSION));
    $allowed_file_types = ['image/png', 'image/jpeg', 'image/jpg'];
      // Vérification de l'extension
      if ($extension != "jpg" && $extension != "jpeg" && $extension != "png") {
        array_push($errors, "L'extension du fichier " . $images['name'][$i] . " n'est pas valide");
        continue;
      }
      if (!in_array($imageInfo['mime'], $allowed_file_types)) {
        array_push($errors, "L'image " . $images['name'][$i] . " n'est pas une vrai image");
        continue;
      }
      
    $totalSize += $images['size'][$i];
    array_push($validImages, $images['name'][$i]);
    // Vérification de la taille
    if ($images['size'][$i] > $maxFileSize) {
      array_push($errors, "La taille du fichier " . $images['name'][$i] . " dépasse la limite de 3 Méga-octets");
    }
    }

  // Vérification de la taille totale
  if ($totalSize > $maxTotalSize) {
    array_push($errors, "La somme des tailles de tous les fichiers dépasse la limite de 70 Méga-octets");
  }
  if (count($errors) > 0) {
    echo "<b>Erreurs :</b>";
    echo "<ul>";
    foreach ($errors as $error) {
      echo "<li>" . $error . "</li>";
    }
    echo "</ul>";
  } else {
    $date = date('Y-m-d H:i:s');
    $dateModif = date('Y-m-d H:i:s');
    try {
      // Début de la transaction
      $pdo->beginTransaction();
  
      $stmt = $pdo->prepare('INSERT INTO post (commentaire, dateDeCreation, dateDeModification) VALUES (:commentaire, :dateModif, :date)');
      $stmt->bindParam(':commentaire', $text);
      $stmt->bindParam(':dateModif', $dateModif);
      $stmt->bindParam(':date', $date);
      $stmt->execute();
      $idPost = $pdo->lastInsertId();
  
      for ($i = 0; $i < count($validImages); $i++) {
          $extension = strtolower(pathinfo($images['name'][$i], PATHINFO_EXTENSION));
          $stmt2 = $pdo->prepare('INSERT INTO media (idPost, nomFichierMedia, typeMedia, dateDeCreation) VALUES (:idPost, :nom, :type, :date)');
          $stmt2->bindParam(':idPost', $idPost);
          $stmt2->bindParam(':nom', $validImages[$i]);
          $stmt2->bindParam(':type', $extension);
          $stmt2->bindParam(':date', $date);
          $stmt2->execute();
          move_uploaded_file($images['tmp_name'][$i], $directory . $validImages[$i]);
      }
      // Si tout est OK, on valide la transaction
      $pdo->commit();
      $_SESSION['valid'] = true;
      header("Location: index.php");
      exit;
  } catch (PDOException $e) {
      // En cas d'erreur, on annule la transaction
      $pdo->rollBack();
      $_SESSION['valid'] = false;
      echo 'Connexion échouée : ' . $e->getMessage();
      exit();
  }
  
  if ($_SESSION['valid'] == false) {
      echo 'l\'insertion n\'a pas marché';
  }
  }
  }
  
       // Affichage des images avec leurs descriptions
      function afficher_image($pdo) {
       $stmt3 = $pdo->prepare('SELECT media.nomFichierMedia, post.commentaire FROM media INNER JOIN post ON media.idPost = post.idPost');
       $stmt3->execute();
       $results = $stmt3->fetchAll(PDO::FETCH_ASSOC);
       foreach ($results as $row) {
        echo '<div style="width : 300px; border-style: solid; margin : 5; padding : 5;background-color : white;">';
           echo '<img src="img_uploads/' . $row['nomFichierMedia'] . '" alt="image" class="img-responsive">';
           echo '<span style="margin : 2; font-size : 20px; font-weight : bold; ">' . $row['commentaire'] . '</span>';
        echo '</div>';
       }
      }
  
  
?>

    

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
$maxFileSize = 10 * 1024 * 1024; // 3 Méga-octets
$maxTotalSize = 70 * 1024 * 1024; // 70 Méga-octets
$media = $_FILES['media'];
$directory = "uploads/";
$text = $_POST['text'];
if(!is_dir($directory)){
  mkdir($directory);
}
if (isset($media)) {
  $totalSize = 0;
$validMedia = array();
for ($i = 0; $i < count($media['name']); $i++) {
  $mediaInfo = mime_content_type($media['tmp_name'][$i]);
    // Vérification de l'extension
    $extension = strtolower(pathinfo($media['name'][$i], PATHINFO_EXTENSION));
    $allowed_file_types = ['image/png', 'image/jpeg', 'image/jpg', 'video/mp4', 'video/mpeg', 'video/quicktime', 'audio/mpeg', 'audio/wav', 'audio/mp3'];
    if (!in_array($mediaInfo, $allowed_file_types)) {
        array_push($errors, "Le fichier " . $media['name'][$i] . " n'est pas une image, une vidéo ou un fichier audio");
        continue;
    } elseif (in_array($mediaInfo, ['video/mp4', 'video/mpeg', 'video/quicktime'])) {
        // Vérification de l'extension de la vidéo
        if ($extension != "mp4" && $extension != "mpeg" && $extension != "mov") {
            array_push($errors, "L'extension de la vidéo " . $media['name'][$i] . " n'est pas valide");
            continue;
        }
    } elseif (in_array($mediaInfo, ['audio/mpeg', 'audio/wav', 'audio/mp3'])) {
        // Vérification de l'extension du fichier audio
        if ($extension != "mp3" && $extension != "wav") {
            array_push($errors, "L'extension du fichier audio " . $media['name'][$i] . " n'est pas valide");
            continue;
        }
    } else {
        // Vérification de l'extension de l'image
        if ($extension != "jpg" && $extension != "jpeg" && $extension != "png") {
            array_push($errors, "L'extension du fichier " . $media['name'][$i] . " n'est pas valide");
            continue;
        }
    }
      
    $totalSize += $media['size'][$i];
    array_push($validMedia, $media['name'][$i]);
    // Vérification de la taille
    if ($media['size'][$i] > $maxFileSize) {
      array_push($errors, "La taille du fichier " . $media['name'][$i] . " dépasse la limite de 3 Méga-octets");
    }
  }

  // Vérification de la taille totale
  if ($totalSize > $maxTotalSize) {
    array_push($errors, "La somme des tailles de tous les fichiers dépasse la limite de 70 Méga-octets");
  }
  
  if (count($errors) > 0) {   
    $_SESSION['valid'] = false;
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
      $uploadSuccessful = true;
      for ($i = 0; $i < count($validMedia); $i++) {
          $extension = strtolower(pathinfo($media['name'][$i], PATHINFO_EXTENSION));
          $stmt2 = $pdo->prepare('INSERT INTO media (idPost, nomFichierMedia, typeMedia, dateDeCreation) VALUES (:idPost, :nom, :type, :date)');
          $stmt2->bindParam(':idPost', $idPost);
          $stmt2->bindParam(':nom', $validMedia[$i]);
          $stmt2->bindParam(':type', $extension);
          $stmt2->bindParam(':date', $date);
          
          if (!move_uploaded_file($media['tmp_name'][$i], $directory . $validMedia[$i])) {
            $uploadSuccessful = false;
            break;
        }
      
          $stmt2->execute(); 
    }
      if ($uploadSuccessful) {
        // Si tout est OK, on valide la transaction
        $pdo->commit();
        $_SESSION['valid'] = true;
        header("Location: index.php");
        

        exit;
    } else {
        // Si l'upload a échoué, on annule la transaction
        $pdo->rollBack();
        $_SESSION['valid'] = false;
        exit();
    }
  } catch (PDOException $e) {
    // En cas d'erreur, on annule la transaction
    $pdo->rollBack();
    $_SESSION['valid'] = false;
    echo 'Connexion échouée : ' . $e->getMessage();
    exit();
}
      // Si tout est OK, on valide la transaction
  
    
    }
  }
       // Affichage des images avec leurs descriptions
       function afficher_medias_posts($pdo) {
        $stmt = $pdo->prepare('SELECT * FROM post ORDER BY idPost DESC');
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            echo '<div style="display: flex; flex-direction: column; justify-content: space-between; width: 300px; border-style: solid; margin: 5px; padding: 5px; background-color: white;">';
            $stmt2 = $pdo->prepare('SELECT nomFichierMedia FROM media WHERE idPost = :idPost');
            $stmt2->bindParam(':idPost', $row['idPost'], PDO::PARAM_INT);
            $stmt2->execute();
            $medias = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            foreach ($medias as $media) {
                echo "<div style='display: flex; justify-content: center;'>";
                $extension = strtolower(pathinfo($media['nomFichierMedia'], PATHINFO_EXTENSION));
                if ($extension == 'mp4' || $extension == 'mpeg' || $extension == 'mov') {
                    echo "<video src='uploads/".$media['nomFichierMedia']."' width='150' height='120' autoplay loop></video>";
                } else if($extension != "mp3" && $extension != "wav")  {
                    echo "<img src='uploads/".$media['nomFichierMedia']."' width='150' height='120' controls autoplay='false'>";
                } else {
                    echo "<img src='uploads/".$media['nomFichierMedia']."' width='150' height='120'>";
                }
                echo "</div>";
            }
            echo "<div style='display: flex; flex-direction: column;'>";
            echo "<span>".$row['commentaire']."</span>";
            echo "<div style='display: flex; justify-content: space-between; align-items: flex-end;'>";
            echo "<form action='modifier_image.php' method='POST'>";
            echo "<input type='hidden' name='idPost' value='".$row['idPost']."'>";
            echo "<input type='submit' name='modifier' value='Modifier'>";
            echo "</form>";
            echo "<form action='supprimer_image.php' method='POST'>";
            echo "<input type='hidden' name='idPost' value='".$row['idPost']."'>";
            echo "<input type='submit' name='supprimer' value='Supprimer'>";
            echo "</form>";
            echo "</div>";
            echo "</div>";
            echo "</div><br><br>";
        }
    }
      
  
?>

    
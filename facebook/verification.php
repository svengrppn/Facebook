
<?php
session_start();
// Connexion à la base de données
try {
  $pdo = new PDO('mysql:host=localhost;dbname=facebook', 'root', 'Super');
} catch (PDOException $e) {
  echo 'Connexion échouée : ' . $e->getMessage();
  exit();
}
$_SESSION['valid'] = false;
$errors = array();
$maxFileSize = 10 * 1024 * 1024; // 3 Méga-octets
$maxTotalSize = 70 * 1024 * 1024; // 70 Méga-octets
$media = $_FILES['media'];
$directory = "uploads/";
$directory_minia = "img_miniatures/";
$text = filter_input(INPUT_POST,'text',FILTER_SANITIZE_SPECIAL_CHARS);
if(!is_dir($directory)){
  mkdir($directory);
}
if(!is_dir($directory_minia)){
  mkdir($directory_minia);
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
        if ($extension != "mp3" && $extension != "wav" && $extension != "mpeg") {
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
    $filename =  md5(uniqid('', true)) . '.' . $extension;
    $stmt2 = $pdo->prepare('INSERT INTO media (idPost, nomFichierMedia, typeMedia, dateDeCreation) VALUES (:idPost, :nom, :type, :date)');
    $stmt2->bindParam(':idPost', $idPost);
    $stmt2->bindParam(':nom', $filename);
    $stmt2->bindParam(':type',  $extension);
    $stmt2->bindParam(':date', $date);


    
    if (!move_uploaded_file($media['tmp_name'][$i], $directory .  $filename)) {
      $uploadSuccessful = false;
      break;
    }
    $stmt2->execute(); 
  }
  // Chemin du fichier original
  $filePath = $directory . $filename;

  // Création de la miniature
  list($width, $height) = getimagesize($filePath);
  $thumbnailWidth = 100; // Largeur de la miniature
  $thumbnailHeight = ($height / $width) * $thumbnailWidth;
  $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
  $source = null;
  switch ($extension) {
      case 'jpeg':
      case 'jpg':
          $source = imagecreatefromjpeg($filePath);
          break;
      case 'png':
          $source = imagecreatefrompng($filePath);
          break;
  }
  imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $width, $height);

  // Enregistrement de la miniature
  $thumbnailPath = $directory_minia . $filename;
  switch ($extension) {
      case 'jpeg':
      case 'jpg':
          imagejpeg($thumbnail, $thumbnailPath);
          break;
      case 'png':
          imagepng($thumbnail, $thumbnailPath);
          break;
  }
  

  if ($uploadSuccessful) {
    // Si tout est OK, on valide la transaction
    $pdo->commit();
    $response = array('success' => true);
    header('Location: index.php');
  } else {
    // Si l'upload a échoué, on annule la transaction
    $pdo->rollBack();
    $response = array('success' => false, 'message' => 'Erreur lors de l\'upload des médias');
  }
} catch (PDOException $e) {
  // En cas d'erreur, on annule la transaction
  $pdo->rollBack();
  $response = array('success' => false, 'message' => 'Erreur lors de l\'insertion du post : ' . $e->getMessage());
}

// Envoi de la réponse
header('Content-Type: application/json');
echo json_encode(array('success' => true));
    }
  }
  // Affichage des images avec leurs descriptions
  function afficher_medias_posts($pdo) {
    try {
      $stmt = $pdo->prepare('SELECT * FROM post ORDER BY idPost DESC');
      $stmt->execute();
      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($results as $row) {
        echo '<div style="display: flex; flex-direction: column; justify-content: space-between; width: 300px; border-style: solid; margin: 5px; padding: 5px; background-color: white;">';
        $stmt2 = $pdo->prepare('SELECT nomFichierMedia FROM media WHERE idPost = :idPost');
        $stmt2->bindParam(':idPost', $row['idPost'], PDO::PARAM_INT);
        $stmt2->execute();
        $medias = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($medias as $lemedia) {
          $mime_type = mime_content_type('uploads/' . $lemedia['nomFichierMedia']);
          echo "<div style='display: flex; justify-content: center;'>";
          if (strpos($mime_type, 'video/') === 0) {
          echo "<video src='uploads/".$lemedia['nomFichierMedia']."' width='150' height='120' autoplay loop></video>";
          } else if (strpos($mime_type, 'image/') === 0) {
          echo "<img src='uploads/".$lemedia['nomFichierMedia']."' width='150' height='120' >";
          } else {
          echo "<audio src='uploads/".$lemedia['nomFichierMedia']."' width='150' height='120' controls>";
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
      $_SESSION['valid'] = true;
    } catch(PDOException $e) {
      echo '<script>alert("L\'insertion a échoué.");</script>';
    }
  }
  
      
  
?>

    
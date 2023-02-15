<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
</body>
</html>
<?php
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
    // Ajouter les images valides à la base de données
    for($i = 0; $i < count($validImages); $i++) {
        $extension = strtolower(pathinfo($images['name'][$i], PATHINFO_EXTENSION));
        $date = date('Y-m-d H:i:s');
        move_uploaded_file($images['tmp_name'][$i], $directory . $validImages[$i]);
        $stmt = $pdo->prepare('INSERT INTO media (nomFichierMedia,typeMedia,dateDeCreation) VALUES (:nom,:type,:date)');
        $stmt->bindParam(':nom', $validImages[$i]);
        $stmt->bindParam(':type', $extension);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
    }
       
    header("Location: index.php");
    }
  }
  

  
  
?>

    
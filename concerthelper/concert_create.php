<?php

include "./includes/connect.php";

$name = $_POST["concert_name"];
$description= $_POST["description"];
$date = $_POST["date"];
$recordingpath = NULL;

if(isset($_FILES["recording"]) && $_FILES['recording']['error'] === UPLOAD_ERR_OK){
    $ext = pathinfo($_FILES['recording']['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . "." . $ext;
    $recordingpath = "./assets/uploads/performances" . $newName;
    move_uploaded_file($_FILES['recording']['tmp_name'], $recordingpath);
}


$cmd = "INSERT INTO concerts (concert_id, description, concert_date, performence_file_name)
        VALUES (?,?,?,?)";
$stmt = $dbh->prepare($cmd);
$result = $stmt->execute([$name, $description, $date,$recordingpath]);


if ($result) {
    echo "Insert successful! Row ID: " . $dbh->lastInsertId();
} else {
    echo "ERROR: Execute failed: ";
    var_dump($stmt->errorInfo());
}


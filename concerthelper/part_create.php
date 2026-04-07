<?php

include "./includes/connect.php";



$name = $_POST["concert_name"];
$instrument_part= $_POST["instrument_part"];
$recordingpath = NULL;

if(isset($_FILES["part"]) && $_FILES['part']['error'] === UPLOAD_ERR_OK){
    $ext = pathinfo($_FILES['part']['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . "." . $ext;
    $recordingpath = "./assets/uploads/parts" . $newName;
    move_uploaded_file($_FILES['part']['tmp_name'], $recordingpath);
}


$cmd = "INSERT INTO parts (part_id,concert_id,instrument_part,file_name)
        VALUES (?,?,?,?)";
$stmt = $dbh->prepare($cmd);
$result = $stmt->execute([$name.$instrument_part,$name,$instrument_part, $recordingpath]);


if ($result) {
    echo "Insert successful! Row ID: " . $dbh->lastInsertId();
} else {
    echo "ERROR: Execute failed: ";
    var_dump($stmt->errorInfo());
}



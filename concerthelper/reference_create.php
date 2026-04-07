<?php

include "./includes/connect.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. PHP started <br>";


$reference_name = $_POST["reference_name"];
$concert_name = $_POST["concert_name"];
$instrument_part = $_POST["instrument_part"];
$recordingpath = NULL;

echo "4. Variables set <br>";

if(isset($_FILES["reference_video"]) && $_FILES['reference_video']['error'] === UPLOAD_ERR_OK){
    $ext = pathinfo($_FILES['reference_video']['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . "." . $ext;
    $recordingpath = "./assets/uploads/reference" . $newName;
    move_uploaded_file($_FILES['reference_video']['tmp_name'], $recordingpath);
}


$cmd = "INSERT INTO recordings (recording_id,concert_id,part_id,file_name)
        VALUES (?,?,?,?)";
$stmt = $dbh->prepare($cmd);

echo "5. Statement prepared <br>";
$result = $stmt->execute([$reference_name,$concert_name,$concert_name.$instrument_part, $recordingpath]);
echo "6. Execute ran <br>";

if ($result) {
    echo "Insert successful! Row ID: " . $dbh->lastInsertId();
} else {
    echo "ERROR: Execute failed: ";
    var_dump($stmt->errorInfo());
}

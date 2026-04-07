<?php

include "./includes/connect.php";


$member_name = $_POST["member_name"];
$member_description = $_POST["member_description"];
$recordingpath = NULL;

if(isset($_FILES["member_photo"]) && $_FILES['member_photo']['error'] === UPLOAD_ERR_OK){
    $ext = pathinfo($_FILES['member_photo']['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . "." . $ext;
    $recordingpath = "./assets/uploads/members" . $newName;
    move_uploaded_file($_FILES['member_photo']['tmp_name'], $recordingpath);
}


$cmd = "INSERT INTO members (member_id, file_name,description)
        VALUES (?,?,?)";
$stmt = $dbh->prepare($cmd);
$result = $stmt->execute([$member_name,$recordingpath,$member_description]);


if ($result) {
    echo "Insert successful! Row ID: " . $dbh->lastInsertId();
} else {
    echo "ERROR: Execute failed: ";
    var_dump($stmt->errorInfo());
}

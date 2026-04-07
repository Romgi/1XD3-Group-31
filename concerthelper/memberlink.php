<?php

include "./includes/connect.php";


$concert_name = $_POST["concert_name"];
$instrument_part = $_POST["instrument_part"];
$reference_name = $_POST["reference_name"];
$member_name = $_POST["member_name"];


$cmd = "INSERT INTO member_concerts (member_concert_id, member_id, part_id, concert_id)
        VALUES (?,?,?,?)";
$stmt = $dbh->prepare($cmd);
$result = $stmt->execute([$member_name.$concert_name,$member_name,$concert_name.$instrument_part,$concert_name]);

$cmd = "INSERT INTO member_parts(member_part_id,member_id,part_id,concert_id)
        VALUES (?,?,?,?)";
$stmt = $dbh->prepare($cmd);
$result = $stmt->execute([$member_name.$instrument_part,$member_name,$concert_name.$instrument_part,$concert_name]);

$cmd = "INSERT INTO member_recordings(member_recording_id,member_id,recording_id,concert_id)
        VALUES (?,?,?,?)";
$stmt = $dbh->prepare($cmd);
$result = $stmt->execute([$member_name.$reference_name,$member_name,$reference_name,$concert_name]);


if ($result) {
    echo "Insert successful! Row ID: " . $dbh->lastInsertId();
} else {
    echo "ERROR: Execute failed: ";
    var_dump($stmt->errorInfo());
}

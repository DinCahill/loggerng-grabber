<?php
require_once "shibbobleh_client.php"
$action = $_GET['action'];
$memberid = $_SESSION['memberid'];


switch (action) {
case "make":
    break;
case "progress":
    break;
case "getrequests":
    break;
default:
    echo "Error: unknown action.";
    exit 1;
}

echo file_get_contents("https://urybsod.york.ac.uk:9090/$action?user=$memberid")
?>
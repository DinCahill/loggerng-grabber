<?php
require_once "traditional_auth.php";
include "config.php";

$logs_path = "/mnt/logs/";

$url = 'http://urybsod.york.ac.uk:'.$port.'/'.$_GET['action'];
function just($x)
{
  return isset($_GET[$x]) ? $_GET[$x] : null;
}

$options = array('user' => 999,
                 'start' => just($_GET['start']) ,
                 'end' => just($_GET['end']),
                 'format' => just($_GET['format']),
		 'title' => just($_GET['title']));
$r = new HttpRequest($url, HttpRequest::METH_GET);
$r->setQueryData(array_filter($options));

switch ($_GET['action']) {
case "make":
case "progress":
    try  {
	$r->send();
	echo $r->getResponseBody();
    } catch (HttpException $ex) {
    echo $ex;
}
break;
case "getrequests":
    try {
	$r->send();
	switch($r->getResponseCode()) {
	case '200':
            echo $r->getResponseBody();
            break;
	case '400':
            header('HTTP/1.1 400 Bad Request');
            echo $r->getResponseBody();
            break;
	default:
            header('HTTP/1.1 500 Internal Server Error');
            echo $r->getResponseBody();
            break;
	}
    } catch (HttpException $ex) {
	echo $ex;
    }
    break;
case "download":
    try {
	$r->send();
	switch ($r->getResponseCode()) {
	case '200':
            $response = json_decode($r->getResponseBody());
            $file = $logs_path.$response->{"filename_disk"};
            $filename_user = $response->{"filename_user"};
            header("X-Sendfile: $file");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"$filename_user\"");
            exit;
	case '403':
            // The log isn't ready yet. Say something about it?
            break;
	default:
            header('HTTP/1.1 500 Internal Server Error');
            echo $r->getResponseBody();
            break;
	}
    } catch (HttpException $ex) {
	echo $ex;
    }
    break;
case "remove":
    try {
        $r->send();
	switch ($r->getResponseCode()) {
        case '200':
            header('HTTP/1.1 200 OK');
            break;
        case '400':
            header('HTTP/1.1 400 Bad Request');
            echo $r->getResponseBody();
            break;
        default:
            header('HTTP/1.1 500 Internal Server Error');
            echo $r->getResponseBody();
            break;
        }
    } catch (HttpException $ex) {
        echo $ex;
    }
case "rename":
    try {
        $r->send();
	switch ($r->getResponseCode()) {
        case '200':
            header('HTTP/1.1 200 OK');
            break;
        case '400':
            header('HTTP/1.1 400 Bad Request');
            echo $r->getResponseBody();
            break;
        default:
            header('HTTP/1.1 500 Internal Server Error');
            echo $r->getResponseBody();
            break;
        }
    } catch (HttpException $ex) {
        echo $ex;
    }
case "allprogress":
    try {
        $r->send();
	switch ($r->getResponseCode()) {
        case '200':
            echo $r->getResponseBody();
            break;
        default:
            header('HTTP/1.1 500 Internal Server Error');
            echo $r->getResponseBody();
            break;
        }
    } catch (HttpException $ex) {
        echo $ex;
    }
	break;
default:
    echo "Error: unknown action.";
    exit(1);
}
?>

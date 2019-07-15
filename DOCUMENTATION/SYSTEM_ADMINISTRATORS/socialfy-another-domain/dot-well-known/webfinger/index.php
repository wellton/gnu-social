<?php

// FIXME: REPLACE          \/ here
define('MY_GNUSOCIAL', 'https://www.example.org/gnusocial/index.php');

/**
 * This is a general solution for when you can't have your GNU social instance in the domain root and for when you want to
 * socialfy from another domain.
 */

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPHEADER, getallheaders());
curl_setopt($ch, CURLOPT_URL, MY_GNUSOCIAL . str_replace('webfinger/', 'webfinger', $_SERVER['REQUEST_URI']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
header($header);
echo $body;
curl_close($ch);
<?php
function readHttpLikeInput()
{
    $f = fopen('php://stdin', 'r');
    $store = "";
    $toread = 0;
    while ($line = fgets($f)) {
        $store .= preg_replace("/\r/", "", $line);
        if (preg_match('/Content-Length: (\d+)/', $line, $m))
            $toread = $m[1] * 1;
        if ($line == "\r\n")
            break;
    }
    if ($toread > 0)
        $store .= fread($f, $toread);
    return $store;
}

$contents = readHttpLikeInput();

function outputHttpResponse($statuscode, $statusmessage, $headers, $body)
{
    $headersString = "";
    foreach ($headers as $value) {
        $headersString = $headersString . $value[0] . ": " . $value[1] . PHP_EOL;
    }
    echo "HTTP/1.1 " . $statuscode . " " . $statusmessage . PHP_EOL . $headersString . PHP_EOL . $body;
}

function processHttpRequest($method, $uri, $headers, $body)
{
    $statusmessage = "";
    $statuscode = "";
    $outputBody = "";
    $outputHeaders = array(
        //"Date" => date('D, d M Y h:i:s e', time()),
        array("Server", "Apache/2.2.14 (Win32)"),
        array("Content-Length", "0"),
        array("Connection", "Closed"),
        array("Content-Type", "text/html; charset=utf-8"),

    );

    if ($uri !== "/api/checkLoginAndPassword" or
        !in_array(array("Content-Type", "application/x-www-form-urlencoded"), $headers)) {

        $statusmessage = "Bad Request";
        $statuscode = "400";
        $outputBody = strtolower($statusmessage);
        $outputHeaders[1][1] = strlen(strval($outputBody));
        return outputHttpResponse($statuscode, $statusmessage, $outputHeaders, $outputBody);
    }
    $data = explode("&", $body);
    $login = explode("=", $data[0])[1];
    $password = explode("=", $data[1])[1];
    if (!file_exists("passwords.txt")) {
        $statusmessage = "Internal Server Error";
        $statuscode = "500";
        $outputBody = strtolower($statusmessage);
        $outputHeaders[1][1] = strlen(strval($outputBody));
        return outputHttpResponse($statuscode, $statusmessage, $outputHeaders, $outputBody);
    }
    if (gettype(strripos(file_get_contents("passwords.txt"), "\n" . $login . ":" . $password . "\r\n")) == "integer") {
        $statusmessage = "OK";
        $statuscode = "200";
        $outputBody = "<h1 style=\"color:green\">FOUND</h1>";
        $outputHeaders[1][1] = strlen(strval($outputBody));
        return outputHttpResponse($statuscode, $statusmessage, $outputHeaders, $outputBody);
    } else {
        $statusmessage = "OK";
        $statuscode = "200";
        $outputBody = "<h1 style=\"color:red\">Incorrect login or password</h1>";
        $outputHeaders[1][1] = strlen(strval($outputBody));
        return outputHttpResponse($statuscode, $statusmessage, $outputHeaders, $outputBody);
    }

}


function parseTcpStringAsHttpRequest($string)
{
    $temp = explode("\n", $string);
    $method = explode(" ", $temp[0])[0];
    $uri = explode(" ", $temp[0])[1];
    $body = $temp[count($temp) - 1];
    $headers = array();
    for ($i = 1; $i < count($temp) - 2; $i++) {
        array_push($headers, explode(": ", $temp[$i]));
    }

    return array(
        "method" => $method,
        "uri" => $uri,
        "headers" => $headers,
        "body" => $body,
    );
}

$http = parseTcpStringAsHttpRequest($contents);
processHttpRequest($http["method"], $http["uri"], $http["headers"], $http["body"]);

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
    if (explode('?', $uri, 2)[0] !== "/sum") {
        $statusmessage = "Not Found";
        $statuscode = "404";
        $outputBody = strtolower($statusmessage);
    } else if (explode('=', explode('?', $uri, 2)[1])[0] !== "nums" or $method !== "GET") {
        $statusmessage = "Bad Request";
        $statuscode = "400";
        $outputBody = strtolower($statusmessage);
    } else {
        $statusmessage = "OK";
        $statuscode = "200";
        $outputBody = strval(array_sum(explode(",", explode("=", $uri)[1])));
    }
    $outputHeaders = array(
        //"Date" => date('D, d M Y h:i:s e', time()),
        array("Server", "Apache/2.2.14 (Win32)"),
        array("Connection", "Closed"),
        array("Content-Type", "text/html; charset=utf-8"),
        array("Content-Length", strlen(strval($outputBody))),
    );
    return outputHttpResponse($statuscode, $statusmessage, $outputHeaders, $outputBody);
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

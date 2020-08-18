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
echo(json_encode($http, JSON_PRETTY_PRINT));

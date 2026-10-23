<?php
// EC-02: Upload, der mittendrin abbricht — Kopf verspricht 300 kB, gesendet werden 40 kB, dann zu.
[$_, $port, $sid, $datei] = $argv;
$b = 'qa12grenze';
$daten = file_get_contents($datei);
$rumpf = "--$b\r\nContent-Disposition: form-data; name=\"onboarding_avatar[_token]\"\r\n\r\ncsrf-token\r\n--$b\r\nContent-Disposition: form-data; name=\"onboarding_avatar[avatar]\"; filename=\"abbruch.png\"\r\nContent-Type: image/png\r\n\r\n".$daten."\r\n--$b--\r\n";
$s = fsockopen('127.0.0.1', (int) $port);
fwrite($s, "POST /de/einfuehrung/profilbild HTTP/1.1\r\nHost: 127.0.0.1:$port\r\nOrigin: http://127.0.0.1:$port\r\nCookie: PHPSESSID=$sid\r\nContent-Type: multipart/form-data; boundary=$b\r\nContent-Length: ".strlen($rumpf)."\r\nConnection: close\r\n\r\n");
fwrite($s, substr($rumpf, 0, 40000));
usleep(200000);
fclose($s);
echo "gesendet: 40000 von ".strlen($rumpf)." Bytes, Verbindung geschlossen\n";

<?php
require '/Users/michaelferreira/DEV/SaaS/endlech/vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;
function txt($f){ $s=file_get_contents($f); $s=html_entity_decode(preg_replace('/<[^>]+>/',' ',$s), ENT_QUOTES|ENT_HTML5,'UTF-8'); return preg_replace('/\s+/u',' ',$s); }
foreach (['de','lb','fr','en','pt'] as $L) {
  $c = Yaml::parseFile("/Users/michaelferreira/DEV/SaaS/endlech/translations/messages.$L.yaml");
  $g = fn($k)=>array_reduce(explode('.',$k), fn($a,$p)=>$a[$p], $c);
  $keys=['badge_text'=>$g('verified.badge_text'),'badge'=>$g('verified.badge'),'na'=>$g('accessibility.not_available'),'nm'=>$g('accessibility.not_measured'),'below'=>str_replace('%min%','90',$g('accessibility.below_minimum'))];
  $e=txt("/tmp/qa12/e-$L.html"); $d=txt("/tmp/qa12/d12-$L.html").txt("/tmp/qa12/d13-$L.html").txt("/tmp/qa12/d15-$L.html");
  foreach($keys as $k=>$v){ printf("%s %-10s %-35s Einführung:%s Detailseite:%s\n",$L,$k,'"'.$v.'"', str_contains($e,$v)?'ja':'NEIN', str_contains($d,$v)?'ja':'NEIN'); }
}

<?

require_once("config.php");
require_once("lib.php");
Header("Content-Type: text/html; charset=$site_charset\n");

dbconn();

$query = "
  SELECT
    esr,name,dup_esr
  FROM
    stations
";
$res = mysql_query($query);
if (!$res)
  die("Error: " . mysql_error());
?>
<style>a { text-decoration: none; }</style>
<h1>Невалидные ЕСР-коды</h1>
Эти коды не проходят проверку по контрольной цифре. Однако вариант с правильно
выставленной контрольной цифрой не обязательно будет правильным, так как опечатка возможна
и в других цифрах! При выверке следует найти правильный код и проставить dup_esr!
<table border=1 cellspacing=0>
<tr><td>Ошибочный</td><td>Правильный</td></tr>
<?

while ($r = mysql_fetch_row($res)) {
  $name = $r[1];
  $d = $r[2];
  $r = $r[0];
  $c = $r[0] + $r[1] * 2 + $r[2] * 3 + $r[3] * 4 + $r[4] * 5;
  $o = $c % 11;
  if ($o == 10) {
    $c = $r[0] * 3 + $r[1] * 4 + $r[2] * 5 + $r[3] * 6 + $r[4] * 7;
    $o = $c % 11;
    if ($o == 10) 
      $o = 0;
  } 
  $valid = substr($r,0,5).$o;
  if ($r[5] != $o) {
    if ($d) $rv = "<s>$r</s></a> -&gt; <a href=./esr:$d>$d";
    else $rv = $r;
    echo "<tr><td><a href=./esr:$r>$rv</a> ($name)</td><td><a href=./esr:$valid>$valid</a></td></tr>\n";
  }

}

?>
</table>
Эти коды принадлежат станциям, которые совпадают по имени и по первым 4 цифрам кода ЕСР. Возможно, они совпадают.
Скорее всего, правильный код - тот, что с нулём и взят из ТР4.
<table border=1 cellspacing=0>
<tr><td>ЕСР</td><td>Название</td></tr>
<?
$query = "
  SELECT
    GROUP_CONCAT(esr),
    name,
    COUNT(id) AS c
  FROM
    stations
  GROUP BY
    UPPER(name),
    SUBSTR(esr,1,4)
  HAVING
    c>1
";

$res = mysql_query($query);
if (!$res)
  die("Error: " . mysql_error());

while ($row = mysql_fetch_row($res)) {
  $esrs = explode(",",$row[0]);
  $name = $row[1];
  $tmp=array();
  foreach($esrs as $esr)
    $tmp[] = "<a href=./esr:".$esr.">".$esr."</a>";
  print "<tr><td>".implode(", ",$tmp)."</td><td>".$name."</td></tr>\n";
}

?>
</table>

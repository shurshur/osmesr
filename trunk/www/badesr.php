<?

require_once("config.php");
require_once("lib.php");
Header("Content-Type: text/html; charset=$site_charset\n");

dbconn();

$query = "
  SELECT
    esr,name
  FROM
    stations
";
$res = mysql_query($query);
if (!$res)
  die("Error: " . mysql_error());
?>
<h1>���������� ���-����</h1>
��� ���� �� �������� �������� �� ����������� �����. ������ ������� � ���������
������������ ����������� ������ �� ����������� ����� ����������, ��� ��� �������� ��������
� � ������ ������! ��� ������� ������� ����� ���������� ��� � ���������� dup_esr!
<table border=1 cellspacing=0>
<tr><td>���������</td><td>����������</td></tr>
<?

while ($r = mysql_fetch_row($res)) {
  $name = $r[1];
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
  if ($r[5] != $o) 
    echo "<tr><td><a href=./esr:$r>$r</a> ($name)</td><td><a href=./esr:$valid>$valid</a></td></tr>\n";

}

?>
</table>
��� ���� ����������� ��������, ������� ��������� �� ����� � �� ������ 4 ������ ���� ���. ��������, ��� ���������.
������ �����, ���������� ��� - ���, ��� � ��̣� � ���� �� ��4.
<table border=1 cellspacing=0>
<tr><td>���</td><td>��������</td></tr>
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

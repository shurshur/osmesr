<?
  require_once("config.php");
  require_once("lib.php");
  Header("Content-Type: text/html; charset=$site_charset\n");
?>
<style>a { text-decoration: none; }</style>
<h1>������ ������� ��������</h1>
<p><a href="http://forum.openstreetmap.org/viewtopic.php?id=9084">���������</a></p>
<?php

dbconn();

$q_stations = 0;
$q_found = 0;
$q_uniq = 0;
$q_nonuniq = 0;
$q_esrnf = 0;

$query = "
  SELECT DISTINCT
    name,
    source,
    country,
    q_stations,
    q_uniq,
    q_nonuniq,
    q_esrnf,
    updated,
    q_found
  FROM
    regions
  WHERE
    name <> ''
    AND q_stations>0
  ORDER BY
    country,
    name
";
if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

$regions = array();

while ($r = mysql_fetch_row($res))
{
  $query = "
    SELECT
      esr_name
    FROM
      regions
    WHERE
      name = '".$r[0]."'
  ";
  if (!($res2 = mysql_query($query)))
    die ("Error: ".mysql_error()."\n");

  $esr_names = array();
  while ($r2 = mysql_fetch_row($res2))
  {
    $tmp = explode(" - ", $r2[0]);
    $esr_names[] = $tmp[0];
  }
  $esr_name_list = implode(", ", $esr_names);
  $regions[] = array("esr_names" => $esr_name_list, "name" => $r[0], "source" => $r[1], "country" => $r[2],
                     "q_stations" => $r[3], "q_uniq" => $r[4], "q_nonuniq" => $r[5], "q_esrnf" => $r[6], "updated" => $r[7],
		     "q_found" => $r[8]);
  $q_stations += $r[3];
  $q_found += $r[8];
  $q_uniq += $r[4];
  $q_nonuniq += $r[5];
  $q_esrnf += $r[6];
}

$query = "
  SELECT 
    esr_name,
    source,
    id,
    q_stations,
    q_uniq,
    q_nonuniq,
    q_esrnf,
    updated,
    q_found
  FROM
    regions
  WHERE 
    name = ''
    AND q_stations>0
";
if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

while ($r = mysql_fetch_row($res))
{
  $tmp = explode(" - ", $r[0]);
  $regions[] = array("name" => $tmp[0], "esr_names" => $tmp[0], "source" => $r[1], "country" => "�������������", "id" => $r[2],
                     "q_stations" => $r[4], "q_uniq" => $r[4], "q_nonuniq" => $r[5], "q_esrnf" => $r[6], "updated" => $r[7], "q_found" => $r[8]);
  $q_stations += $r[3];
  $q_found += $r[8];
  $q_uniq += $r[4];
  $q_nonuniq += $r[5];
  $q_esrnf += $r[6];
}
mysql_free_result($res);

$query = "
  SELECT
    count(id)
  FROM
    stations
  WHERE 
    region_id = 0
";

if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

$r = mysql_fetch_row($res);

if ($r[0] > 0) 
  $regions[] = array("name" => "*** (������ �� ����������) ***", "esr_names" => "*** (������ �� ����������) ***", "source" => '', "country" => "�������������", "id" => 0, "q_stations" => $r[0], "q_uniq" => 0, "q_nonuniq" => 0, "updated" => "", "q_found" => "");

echo "<table border=1 cellspacing=0>\n<tr><td align=center><b>������</b></td><td align=center><b>%%</b></td><td align=center><b>���</b></td><td align=center><b>���.</b></td><td align=center><b>�����.</b></td><td align=center><b>���<b></td><td align=center><b>���������</b></tr>\n";

$country = '';
foreach ($regions as $region)
{
  if ($region["country"]!=$country) 
  {
    $country = $region["country"];
    echo "<tr><td><b><font size=5>".$country."</font></b></td>";
    for($i=0;$i<6;$i++) print "<td>&nbsp;</td>";
    print "</tr>\n";
  }
  if ($region["source"]!="") 
  {
    ?>
      <tr><td><? if($region["country"]) print "&raquo;"; ?> <a href="./region:<?=$region["source"]?>:l">
        <?=$region["name"]?>
      </a></td>
    <?
    $p = 0;
    if ($region["q_stations"] )
      $p = round($region["q_found"]*100/$region["q_stations"]);
    echo "<td align=right>".$p."%</td>\n";
    echo "<td align=right>".$region["q_found"]."/".$region["q_stations"]."</td>\n";
    echo "<td align=right><font color=green>".$region["q_uniq"]."</font></td>\n";
    echo "<td align=right><font color=goldenrod>".$region["q_nonuniq"]."</font></td>\n";
    echo "<td align=right><font color=red>".$region["q_esrnf"]."</font></td>\n";
    if ($region["updated"])
      echo "<td align=right>".date("H:i:s d.m.Y",$region["updated"])."</td>\n";
    else
      echo "<td>&nbsp;</td>\n";
    echo "</tr>\n";
  } else {
    echo "<tr><td>&raquo; <a href=\"./region:".$region["id"]."\">".$region["name"]."</a></td><td>&nbsp;</td><td align=right>".$region["q_stations"]."</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
  }
}

$p = 0;
if ($q_stations)
  $p = round($q_found*100/$q_stations);
echo "<tr><td><b>�����</b></td><td align=right>$p%</td><td align=right>$q_found/<br>$q_stations</td><td align=right>$q_uniq</td><td align=right>$q_nonuniq</td><td align=right>$q_esrnf</td><td>&nbsp;</td></tr></table>\n";

?>
<ul>
<li>%% - ��������� ����� �������, ��������� ���������� � OSM, � ����� ������� � ���
<li>��� - ����� ���������� ��������� � OSM � ����� ����� ������� � ��� - �� �������� ������������������ ��������� (������������ � ������ ������� ��ޣ��������)
<li>���. - ������� ���������� � OSM - ����� �������� � OSM, ������� ������� ����������� ������� � ��� (�������������� � ������ ��̣���).
<li>�����. - ������� ������������ � OSM - ����� �������� � OSM, ������� ������� ��������� ������� � ��� (������ �����, � ���������� ���������; �������������� � ������ ֣����)
<li>��� - �� ������� � ��� - ����� �������� � OSM, ������� ������ �� ������� ������� ������������ � ��� (��������, �������� ������� �������; ������������ ��������� ������� ��� ��������)
<li>��������� - ����� ���������� �������� ������� ������ ��� ����� �������. �������������� ���������� ����������� � 16:00 ��������� � ������������, � ����������� ��
�������� ������� � ������ � ��������, �� ����.
<li>�������� ������:
<ul>
<!--<li>�����������: OSM XML - <a href="http://downloads.cloudmade.com/europe/azerbaijan">Cloudmade</a>, ������� ������� - <a href="http://gis-lab.info/qa/osm-poly.html">Gis-Lab</a>
<li>�������: OSM XML - <a href="http://downloads.cloudmade.com/europe/armenia">Cloudmade</a>, ������� ������� - �����������, �� ���� ������� � OSM-->
<li>��������, �������: OSM XML - <a href="/osm">�����������</a>, �������� ������� - �����������, �� ���� ������ � OSM, �������� ���� - <a href="http://gis-lab.info/projects/osm-export.html">Gis-Lab</a>
<li>�������, ������, �����, �������: OSM XML - <a href="http://download.geofabrik.de/osm/europe/">Geofabrik</a>.
<li>��� ��������� �������: Gis-Lab PostGIS
</ul>
</ul>
������ ��������� ����������: <b><? print file_get_contents("status"); ?></b>
<p>���� ����������� <a href="http://www.openstreetmap.org/user/Sergey%20Gladilin">Sergey Gladilin</a>. ���������� � ���������� - <a href="http://www.openstreetmap.org/user/Sergey%20Gladilin">Sergey Gladilin</a> � <a href="http://www.openstreetmap.org/user/Alexandr%20Zeinalov">Alexandr Zeinalov</a>.
<br>

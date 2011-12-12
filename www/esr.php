<?
  require_once("config.php");
  require_once("lib.php");
  Header("Content-Type: text/html; charset=$site_charset\n");
  addhiddenframe();
?>
<style>a { text-decoration: none; }</style>
<h1><a href='./'>Единая сетевая разметка</a></h1>
<?php

dbconn();

$esr = $_GET['esr'];
if(!preg_match("/^\d{6}$/",$esr))
  die;

$query = "SELECT id,name FROM station_types";
if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

$stypes = array(0=>"");
while($row = mysql_fetch_assoc($res))
  $stypes[$row["id"]]=$row["name"];

$query = "
  SELECT
    stations.esr AS esr,
    stations.express_code AS express_code,
    express.name AS express_name,
    stations.name AS name,
    stations.name_rzd0 AS name_rzd0,
    stations.name_tr4k1 AS name_tr4k1,
    stations.name_rwua AS name_rwua,
    stations.name_yarasp AS name_yarasp,
    stations.yarasp_addr AS yarasp_addr,
    regions.esr_name AS region,
    railways.name AS railway,
    divisions.name AS division,
    railways.map_url AS map_url,
    station_type_id AS stype,
    stations.name_tr4k2 as name_tr4k2
  FROM
    stations
    LEFT JOIN regions ON stations.region_id = regions.id
    LEFT JOIN railways ON stations.railway_id = railways.id
    LEFT JOIN divisions ON stations.division_id = divisions.id
    LEFT JOIN express ON stations.express_code = express.express_code
  WHERE
    stations.esr = '".mysql_real_escape_string($esr)."'
";

if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

if(mysql_num_rows($res)<=0)
  die;
$row = mysql_fetch_assoc($res);
// Dirty hack by Glad
// $row["name_tr4k2"] = "(данные еще не обработаны)";
// End dirty hack
print "<h2>".$row['esr'].": ".$row['name']."</h2>\n";

$fields=array(
  "esr" => "Код ЕСР",
  "express_code" => "Код Экспресс-3",
  "region" => "Регион",
  "railway" => "Железная дорога",
  "division" => "Отделение",
  "name" => "Название",
  "name_rzd0" => "Название (РЖД)",
  "name_tr4k2" => "Название (Тарифное руководство N4)",
  "name_tr4k1" => "Название (ТР4, справочник тарифных расстояний)",
  "name_rwua" => "Название (Укрзализныци)",
  "name_yarasp" => "Название (Яндекс.Расписания)",
  "yarasp_addr" => "Адрес (Яндекс.Расписания)",
  "stype" => "Статус",
);

if($row['express_code'])
  $row['express_code'] = "<a href=./express:".$row['express_code'].">".$row['express_code']."</a> (".$row['express_name'].")";

if($row['map_url'])
  $row['railway'] = "<a href=\"".$row["map_url"]."\">".$row['railway']."</a>";

$row["stype"] = $stypes[$row["stype"]];

print "<table border=0>\n";
print "<tr><td colspan=3><hr></td></tr>\n";

foreach($fields as $k=>$v) {
  print "<tr><td align=right><b>$v:</b></td><td>&nbsp;</td><td align=left>".$row[$k]."</td></tr>\n";
}

$query = "
  SELECT 
    neighb_esr,
    name
  FROM 
    stations,
    neighb_stations
  WHERE 
    neighb_stations.station_esr=$esr AND
    stations.esr = neighb_stations.neighb_esr
";
if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

$cnt = 0;
while($row = mysql_fetch_row($res)) {
  print "<tr><td align=right>".(!$cnt?"<b>Соседние станции (ТР4):</b>":"&nbsp;")."</td><td>&nbsp;</td>\n";
  print "<td align=left><a href=\"./esr:".$row[0]."\">".$row[0]."</a>&nbsp;".$row[1]."</td></tr>\n";
  $cnt++;
}

print "<tr><td colspan=3><hr></td></tr>\n";
print "</table>\n";

$query = "
  SELECT 
    osm2esr.esr AS esr,
    osmdata.type AS type,
    osmdata.osm_id AS osm_id,
    osmdata.lat AS lat,
    osmdata.lon AS lon,
    osmdata.name AS name,
    osmdata.alt_name AS alt_name,
    osmdata.railway AS railway,
    osm2esr.status AS status
  FROM
    osm2esr,
    osmdata
  WHERE
    osm2esr.esr = '".mysql_real_escape_string($esr)."' AND
    osmdata.id = osm2esr.osmdata_id
";

if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

print "<h3>Найдены в OSM:<h3>";
print("<table border=0>");
$types = array(0=>"node",1=>"way");

while($row = mysql_fetch_assoc($res))
  #print $row["lat"];
  print "<tr><td>".osmdataurl($row["type"],$row["osm_id"],$row["name"],$row["lat"],$row["lon"],$row["railway"])."</td></tr>";

print "</table>\n";

?>

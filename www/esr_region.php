<?
require_once("config.php");
require_once("lib.php");

//////////////////////// ************* MODEL ***************** ///////////////////////////////
$output = array();
Header("Content-Type: text/html; charset=$site_charset\n"); 
setlocale(LC_ALL, $site_locale);

dbconn();

$region_id = intval($_GET["esr_region"]);

$query = "
  SELECT
    esr_name,
    source
  FROM
    regions
  WHERE
    id = $region_id
";
if (!($res = mysql_query($query)))
  die ("Error 0: ".mysql_error()."\n");
 
$r = mysql_fetch_row($res);
$esr_name = explode(' - ', trim($r[0]));
$esr_name = $esr_name[0];
$region = $r[1];
mysql_free_result($res);

$output["region_name"] = $r[0];

$query = "
  SELECT
    stations.name,
    regions.esr_name,
    stations.esr,
    stations.osmnode,
    railways.name,
    divisions.name,
    railways.map_url,
    stations.dup_esr,
    name_rzd0,
    name_tr4k1,
    name_rwua,
    gdevagon_lat,
    gdevagon_lon,
    express_code,
    name_yarasp,
    yarasp_lat,
    yarasp_lon,
    yarasp_id,
    station_types.name,
    stations.railway_id
  FROM
    stations
    LEFT JOIN regions ON stations.region_id = regions.id
    LEFT JOIN railways ON stations.railway_id = railways.id
    LEFT JOIN divisions ON stations.division_id = divisions.id
    LEFT JOIN station_types ON stations.station_type_id = station_types.id
  WHERE 
    stations.region_id = $region_id
  ORDER BY
    stations.name
";

if (!($res = mysql_query($query)))
  die ("Error 1: ".mysql_error()."\n");

$esrs = array();
$output["rows"] = array();
while ($r = mysql_fetch_row($res))
{
  $output_row = array();
  
  $esr = $r[2];
  $output_row["esr"] = $esr;
  $output_row["name"] = $r[0];
  $tmp = explode (' - ', $r[1]);
  $output_row["subregion"] = $tmp[0];

  $output_row["neighbour"] = array();

  $output_row["osmnodes"] = array();

  $output_row["railway"] = $r[4];
  $output_row["division"] = $r[5];
  $output_row["railway_map_url"] = $r[6];
  $output_row["dup_esr"] = $r[7];

  $output_row["names"] = array();
  if ($r[8] != "") 
    $output_row["names"]["rzd0"] = $r[8];
  if ($r[9] != "") 
    $output_row["names"]["tr4"] = $r[9];
  if ($r[10] != "") 
    $output_row["names"]["rwua"] = $r[10];
  if ($r[14] != "") 
    $output_row["names"]["yarasp"] = $r[14];

  $output_row["gdevagon"] = array();
  $output_row["gdevagon"]["lat"] = $r[11];
  $output_row["gdevagon"]["lon"] = $r[12];

  $output_row["yarasp"] = array();
  $output_row["yarasp"]["lat"] = $r[15];
  $output_row["yarasp"]["lon"] = $r[16];
  $output_row["yarasp"]["id"] = $r[17];

  $output_row["express_code"] = $r[13];
  $output_row["station_type"] = $r[18];
  $output_row["railway_id"] = $r[19];

  $output["rows"][$esr] = $output_row;
  $esrs[] = $esr;
}
unset($output_row);
mysql_free_result($res);

$esr_list = implode(",", $esrs);

$query = "
  SELECT 
    neighb_stations.station_esr,
    stations.name,
    regions.name,
    regions.source,
    stations.region_id
  FROM 
    stations
    LEFT JOIN regions ON regions.id=stations.region_id,
    neighb_stations
  WHERE 
    neighb_stations.station_esr in ($esr_list) AND
    stations.esr = neighb_stations.neighb_esr
";

if (!($res = mysql_query($query)))
  die ("Error 2: ".mysql_error()."\n");

while ($r = mysql_fetch_row($res))
{
  $tmp = array();
  $tmp["name"] = $r[1];
  if ($r[3] != $region) {
    $tmp["region_name"] = $r[2];
    $tmp["region_code"] = $r[3]; 
    $tmp["region_id"] = $r[4];
  }
  $output["rows"][$r[0]]["neighbour"][] = $tmp;
}

mysql_free_result($res);


//////////////////////// ************* VIEW ***************** ///////////////////////////////
?>
<style>a { text-decoration: none; }</style>
<h1><a href="./">Единая сетевая разметка</a></h1>
<h3><?
  if ($output["region_name"] != "")
    echo $output["region_name"];
  else
    echo "Станции, регион которых не установлен";
?></h3>
<table border="1" cellspacing="0" cellpadding="0">
  <tr>
    <th>
      ЕСР
    </th>
    <th>
      Станция
    </th>
    <th>
      Статус
    </th>
    <th>
      Источник
    </th>
    <th>
      Соседние станции
    </th>
    <th>
      Подчинение
    </th>
    <th>
      Искать
    </th>
  </tr>
<? $color = array(0 => "white", 1 => "lightgreen", 2 => "yellow"); ?>
<? foreach ($output["rows"] as $output_row) { ?>
  <tr>
    <td>
      <? 
        $tmp = $output_row["esr"];
        if ($output_row["dup_esr"] != "")
	  $tmp = "<strike>$tmp</strike>";
        echo "<a name=\"".$output_row["esr"]."\"></a><a href=\"./esr:".$output_row["esr"]."\">".$tmp."</a>";
      ?>
    </td>
    <td>
      <?
        $tmp = $output_row["name"];
        if ($output_row["dup_esr"] != "")
	  $tmp = "<strike>$tmp</strike>";
        echo $tmp;
      ?>
    </td>
    <td>
      <?
        $tmp = $output_row["station_type"];
        if ($output_row["dup_esr"] != "")
	  $tmp = "<strike>$tmp</strike>";
	if ($tmp == "")
	  $tmp = "&nbsp;";
        echo $tmp;
      ?>
    </td>
    <td>
      <?
	$tmp = array();
	if (isset($output_row["names"]["rzd0"])) 
	  $tmp[] = "РЖД";
	if (isset($output_row["names"]["tr4"])) 
	  $tmp[] = "ТР4";
	if (isset($output_row["names"]["rwua"])) 
	  $tmp[] = "УЗ";
	if (isset($output_row["names"]["yarasp"])) 
	  $tmp[] = "ЯР";

        if (count($tmp) > 0) {
	  $tmp = implode(", ", $tmp);
          if ($output_row["dup_esr"] != "")
	    $tmp = "<strike>$tmp</strike>";
	  echo $tmp;
	} else {
	  echo "&nbsp;";
	}
      ?>
    </td>
    <td>
      <?
        $neighbours = array();
        foreach ($output_row["neighbour"] as $neighbour) {
	  $tmp = $neighbour["name"]; 
          if ($neighbour["region_code"] != "") {
	    $tmp .= " (<a href=\"./region:";
	    $tmp .= $neighbour["region_code"]."\">".$neighbour["region_name"]."</a>)";
	  }
	  if ($neighbour["region_id"] === "0") {
	    $tmp .= " (<a href=\"./region:";
	    $tmp .= $neighbour["region_id"]."\">???</a>)";
	  }
	  $neighbours[] = $tmp;
	}
	if (count($neighbours)>0) 
	  echo implode (", ", $neighbours);
	else
	  echo "&nbsp;";
      ?>
    </td>
    <td>
      <? 
        $tmp = "";
//        if ($output_row["railway_map_url"] != '') $tmp .= "<a href='".$output_row["railway_map_url"]."'>";
        $tmp .= "<a href='./railway:".$output_row["railway_id"]."'>";
        $tmp .= $output_row["railway"]." ж.д.";
//        if ($output_row["railway_map_url"] != '') $tmp .= "</a>";
        $tmp .= "</a>";
	if ($output_row["division"] != '') $tmp .= ", ".$output_row["division"]." отд."; 
        if ($output_row["dup_esr"] != "")
	  $tmp = "<strike>$tmp</strike>";
        echo $tmp;
      ?>
    </td>
    <td>
      <? 
        $tmp = array();
	if ($output_row["yarasp"]["lat"] != 0 && $output_row["yarasp"]["lon"] != 0) {
          $tmp2 = "<a href='http://rasp.yandex.ru/info/station/"; 
          $tmp2 .= $output_row["yarasp"]["id"]."'>rasp.yandex.ru</a>";
	  $tmp[] .= $tmp2;
	}
        if ($output_row["express_code"] != "") {
	  $tmp2 = "<a href='http://www.tutu.ru/poezda/station/map/";
	  $tmp2 .= $output_row["express_code"]."'>tutu.ru</a>";
	  $tmp[] .= $tmp2;
	}
	if ($output_row["gdevagon"]["lat"] != 0 && $output_row["gdevagon"]["lon"] != 0) {
          $tmp2 = "<a href='http://www.gdevagon.ru/scripts/info/station_detail.php?stid="; 
          $tmp2 .= substr($output_row["esr"],0,5)."'>gdevagon.ru</a>";
	  $tmp[] .= $tmp2;
	}
	$tmp2  = $output["region_name"].", станция ".$output_row["name"];
	$tmp2  = urlencode(iconv("koi8-r", "windows-1251", $tmp2));
	$tmp2  = "<a href='http://maps.yandex.ru/?text=" . $tmp2;
	$tmp2 .= "'>maps.yandex.ru</a>";
	$tmp[] = $tmp2;

        if (count($tmp) > 0) {
	  echo implode(",<br>", $tmp);
	} else {
	  echo "&nbsp;";
	}
      ?>
    </td>
    </tr>
  <?
}
?>
</table>

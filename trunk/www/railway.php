<?
  require_once("config.php");
  require_once("lib.php");
  Header("Content-Type: text/html; charset=$site_charset\n");
?>
<style>a { text-decoration: none; }</style>
<h1><a href='./'>Единая сетевая разметка</a></h1>
<?php

dbconn();

$id = intval($_GET['id']);

if (!$id) 
  die();

$query = "
  SELECT
    name,
    url,
    map_url
  FROM
    railways
  WHERE
    id = $id   
";

if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

if(mysql_num_rows($res)<=0)
  die;
$r = mysql_fetch_row($res);
echo "<h2>".$r[0]." ж.д.</h2>\n";

echo "<table border='0'>";
if ($r[1] != '')
  echo "<tr><td>Официльный сайт</td><td>&nbsp;</td><td><a href='".$r[1]."'>".$r[1]."</td></tr>";
if ($r[2] != '')
  echo "<tr><td>Схема дороги</td><td>&nbsp;</td><td><a href='".$r[2]."'>скачать</a></td></tr>";
echo "</table>";

$query = "
  SELECT
    divisions.name,
    divisions.map_url,
    division_types.name
  FROM
    divisions
    LEFT JOIN division_types ON division_type_id=division_types.id
  WHERE
    railway_id = $id AND
    divisions.name <> ''
";

if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

$divisions = array();
while ($r = mysql_fetch_row($res)) {
  if (!isset($divisions[$r[2]])) 
    $divisions[$r[2]] = array();
  $divisions[$r[2]][] = array("name" => $r[0], "map_url" => $r[1]);
}

foreach ($divisions as $cat => $divs) {
  echo "<h4>".$cat."</h4>\n<ul>\n";
  foreach ($divs as $div) {
    echo "<li>".$div["name"];
    if ($div["map_url"] != '') {
      echo " (";
      echo "<a href='".$div["map_url"]."'>схема</a>";
      echo ")";
    }
    echo "</li>\n";
  }
  echo "</ul>\n";
}
?>

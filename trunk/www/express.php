<?
  require_once("config.php");
  require_once("lib.php");
  Header("Content-Type: text/html; charset=$site_charset\n");
?>
<style>a { text-decoration: none; }</style>
<h1><a href='./'>Единая сетевая разметка</a></h1>
<?php

dbconn();

$exp = $_GET['exp'];
if(!preg_match("/^\d{7}$/",$exp))
  die;


$query = "
  SELECT
    express.express_code AS express_code,
    express.name AS name,
    express.express_railway AS express_railway,
    express.alias AS alias,
    stations.esr AS esr,
    stations.name AS esr_name
  FROM
    express
    LEFT JOIN stations ON express.express_code = stations.express_code
  WHERE
    express.express_code = '".mysql_real_escape_string($exp)."'
";

if (!($res = mysql_query($query)))
  die ("Error: ".mysql_error()."\n");

if(mysql_num_rows($res)<=0)
  die;

$row = mysql_fetch_assoc($res);

print "<h2>".$row['express_code'].": ".$row['name']."</h2>\n";

$fields = array(
  "express_code" => "Код Экспресс-3",
  "name" => "Название",
  "express_railway" => "Код ж/д",
  "alias" => "Коды подстанций<sup>*</sup>",
  "esr" => "Код ЕСР"
);

print "<table border=0>\n";
print "<tr><td colspan=3><hr></td></tr>\n";

if($row["alias"]) {
  $query = "
    SELECT
      express_code AS express_code,
      name AS name
    FROM
      express
    WHERE
      express_code IN (".$row["alias"].")
  ";

  $res = mysql_query($query);
  if (!($res = mysql_query($query)))
    die ("Error: ".mysql_error()."\n");

  $aname = array();
  while($r = mysql_fetch_row($res)) {
    $aname[$r[0]] = $r[1];
  }

  $alias = explode(",", $row["alias"]);

  foreach($alias as $k=>$v)
    $alias[$k] = "<a href=./express:$v>$v</a> (".$aname[$v].")";
  $row["alias"] = implode("<br/>", $alias);
}

if($row["esr"])
  $row["esr"] = "<a href=./esr:".$row["esr"].">".$row["esr"]."</a> (".$row["esr_name"].")";

foreach($fields as $k=>$v) {
  print "<tr><td align=right valign=top><b>$v:</b></td><td>&nbsp;</td><td align=left>".$row[$k]."</td></tr>\n";
}

print "<tr><td colspan=3><hr></td></tr>\n";
print "</table>\n";
?>
<p><sup>*</sup>Некоторые коды, например, <a href=./express:2000000>2000000</a>,
относятся к многовокзальным городам, а не к станциям.</p>

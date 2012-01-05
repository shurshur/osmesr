<?
  require_once("config.php");
  require_once("lib.php");
  Header("Content-Type: text/html; charset=$site_charset\n");
  date_default_timezone_set("Europe/Moscow");
?>
<style>a { text-decoration: none; }</style>
<h1>Единая сетевая разметка</h1>
<p>Единая сетевая разметка (ЕСР) — система цифрового обозначения станций на территории 
стран бывшего СССР.</p>
<p><a href="http://forum.openstreetmap.org/viewtopic.php?id=9084">Обсуждение на форуме OpenStreetMap</a>.</p>
<p>Данные в CSV: <a href="esr.csv">ЕСР</a>, <a href="osm2esr.csv">OSM-ЕСР</a>, <a href="express.csv">Экспресс</a>.<p>

<p>
<form action="search" method="post">
Поиск: <input type=text name=q value="" size=32>
<input type=submit value=Поиск>
</form>
</p>
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
    q_found,
    iso3166
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
		     "q_found" => $r[8], "iso3166" => $r[9]);
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
    q_found,
    iso3166
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
  $regions[] = array("name" => $tmp[0], "esr_names" => $tmp[0], "source" => $r[1], "country" => "Неразобранное", "id" => $r[2],
                     "q_stations" => $r[4], "q_uniq" => $r[4], "q_nonuniq" => $r[5], "q_esrnf" => $r[6], "updated" => $r[7], "q_found" => $r[8], "iso3166" => $r[9]);
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
  $regions[] = array("name" => "*** (регион не установлен) ***", "esr_names" => "*** (регион не установлен) ***", "source" => '', "country" => "Неразобранное", "id" => 0, "q_stations" => $r[0], "q_uniq" => 0, "q_nonuniq" => 0, "updated" => "", "q_found" => "");

echo "<table border=1 cellspacing=0>\n<tr><td align=center><b><font size=-1>ISO3166</font></b><td align=center><b>Регион</b></td><td align=center><b>%%</b></td><td align=center><b>ЕСР</b></td><td align=center><b>Одн.</b></td><td align=center><b>Неодн.</b></td><td align=center><b>Нет<b></td><td align=center><b>Обновлено</b></tr>\n";

$country = '';
foreach ($regions as $region)
{
  if ($region["country"]!=$country) 
  {
    $country = $region["country"];
    echo "<tr><td>&nbsp;</td><td><b><font size=5>".$country."</font></b></td>";
    $q = "SELECT * FROM regions WHERE country='$country' GROUP BY source";
    $cres = mysql_query($q);
    $cq_stations = 0;
    $cq_found = 0;
    $cq_uniq = 0;
    $cq_nonuniq = 0;
    $cq_esrnf = 0;
    $c_updated = 0;
    while($crow = mysql_fetch_assoc($cres)) {
      $cq_stations += $crow["q_stations"];
      $cq_found += $crow["q_found"];
      $cq_uniq += $crow["q_uniq"];
      $cq_nonuniq += $crow["q_nonuniq"];
      $cq_esrnf += $crow["q_esrnf"];
      if($crow["updated"] && (!$c_updated || $c_updated>$crow["updated"]))
        $c_updated = $crow["updated"];
    }
    $p = 0;
    if ($cq_stations)
      $p = round($cq_found*100/$cq_stations);
    echo "<td align=right>".$p."%</td>\n";
    echo "<td align=right>".$cq_found."/<br/>".$cq_stations."</td>\n";
    echo "<td align=right><font color=green>".$cq_uniq."</font></td>\n";
    echo "<td align=right><font color=goldenrod>".$cq_nonuniq."</font></td>\n";
    echo "<td align=right><font color=red>".$cq_esrnf."</font></td>\n";
    if ($c_updated)
      echo "<td align=right>".date("H:i:s d.m.Y",$c_updated)."</td>\n";
    else
      echo "<td>&nbsp;</td>\n";
    print "</tr>\n";
  }
  if ($region["source"]!="") 
  {
    ?>
      <tr><td><? if($region["iso3166"]) print $region["iso3166"]; else print "&nbsp;"; ?></td><td><? if($region["country"]) print "&raquo;"; ?> <a href="./region:<?=$region["source"]?>:l">
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
echo "<tr><td>&nbsp;</td><td><b>Всего</b></td><td align=right>$p%</td><td align=right>$q_found/<br>$q_stations</td><td align=right>$q_uniq</td><td align=right>$q_nonuniq</td><td align=right>$q_esrnf</td><td>&nbsp;</td></tr></table>\n";

?>
<ul>
<li>%% - отношение числа станций, найденных ОДНОЗНАЧНО в OSM, к числу станций в ЕСР
<li>ЕСР - Число однозначно найденных в OSM и общее число станций в ЕСР - не включает идентифицированные дубликаты (отображаются в списке станций зачёркнутыми)
<li>Одн. - Найдено однозначно в OSM - число объектов в OSM, которым найдена однозначная станция в ЕСР (подсвечиваются в списке зелёным).
<li>Неодн. - Найдено неоднозначно в OSM - число объектов в OSM, которым найдено несколько станций в ЕСР (скорее всего, с одинаковым названием; подсвечиваются в списке жёлтым)
<li>Нет - Не найдено в ЕСР - число объектов в OSM, которым вообще не найдено никаких соответствий в ЕСР (возможно, название станции неверно; отображаются отдельным списком под таблицей).
<li>Обновлено - время последнего удачного запуска робота для этого региона. Автоматическое обновление запускается в 12:00 ежедневно и продолжается около часа.
<li>Источник данных - Gis-Lab PostGIS.
</ul>
Статус планового обновления: <b><? print file_get_contents("status"); ?></b>
<p>&copy; Идея принадлежит <a href="http://www.openstreetmap.org/user/Sergey%20Gladilin">Sergey Gladilin</a>. Разработка и реализация - <a href="http://www.openstreetmap.org/user/Sergey%20Gladilin">Sergey Gladilin</a> и <a href="http://www.openstreetmap.org/user/Alexandr%20Zeinalov">Alexandr Zeinalov</a>.
<br>&copy; <a href="http://code.google.com/p/osmesr/">Исходный код</a> доступен по лицензии <a href="http://www.gnu.org/licenses/gpl.html">GPL 3.0</a>.
<br>&copy; Использованные изображения частично основаны на картинках с сайтов <a href="http://wikipdia.org/">wikipedia.org</a> и
<a href="http://wiki.openstreetmap.org/">wiki.openstreetmap.org</a> и доступны по лицензии
<a href="http://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA 3.0</a>.</p>

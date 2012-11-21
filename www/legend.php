<?

require_once("config.php");
require_once("lib.php");
Header("Content-Type: text/html; charset=$site_charset\n");

dbconn();
?>
<style>a { text-decoration: none; }</style>
<h1><a href="./">Единая сетевая разметка</a></h1>
<h2>Легенда</h2>
<h3>Типы станций ЕСР</h3>
<ul>
<li><img src="st0.png" /> неизвестный</li>
<?
  $res = mysql_query("SELECT id,name FROM station_types");
  while($row = mysql_fetch_row($res))
    print "<li><img src=\"st".$row[0].".png\"> ".$row[1]."</li>\n";
  mysql_free_result($res);
?>
</ul>
<h3>Типы объектов</h3>
<ul>
<li><img src="node.png" /> точка</li>
<li><img src="way.png" /> линия</li>
<li><img src="relation.png" /> отношение</li>
</ul>
<h3>Типы станций OSM</h3>
<ul>
<li><img src="station.png" /> railway=station</li>
<li><img src="halt.png" /> railway=halt</li>
</ul>
<h3>Источники</h3>
<ul>
<li><b>РЖД</b> - сайт РЖД (Российских Железных Дорог)</li>
<li><b>УЗ</b> - сайт Укрзализныця (Украинских Железных Дорог)</li>
<li><b>ТР4</b> - тарифное руководство N 4</li>
<li><b>ЯР</b> - Яндекс.Расписания</li>
<li><b>ЭТП</b> - сайт ЭТП РЖД (справочник НСИ)</li>
</ul>
<h3>Другое</h3>
<ul>
<li><img src="edit.png"> ссылка на JOSM Remote Control</li>
</ul>

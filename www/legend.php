<?

require_once("config.php");
require_once("lib.php");
Header("Content-Type: text/html; charset=$site_charset\n");

dbconn();
?>
<style>a { text-decoration: none; }</style>
<h1><a href="./">������ ������� ��������</a></h1>
<h2>�������</h2>
<h3>���� ������� ���</h3>
<ul>
<li><img src="st0.png" /> �����������</li>
<?
  $res = mysql_query("SELECT id,name FROM station_types");
  while($row = mysql_fetch_row($res))
    print "<li><img src=\"st".$row[0].".png\"> ".$row[1]."</li>\n";
  mysql_free_result($res);
?>
</ul>
<h3>���� ��������</h3>
<ul>
<li><img src="node.png" /> �����</li>
<li><img src="way.png" /> �����</li>
<li><img src="relation.png" /> ���������</li>
</ul>
<h3>���� ������� OSM</h3>
<ul>
<li><img src="station.png" /> railway=station</li>
<li><img src="halt.png" /> railway=halt</li>
</ul>
<h3>���������</h3>
<ul>
<li><b>���</b> - ���� ��� (���������� �������� �����)</li>
<li><b>��</b> - ���� ������������ (���������� �������� �����)</li>
<li><b>��4</b> - �������� ����������� N 4</li>
<li><b>��</b> - ������.����������</li>
</ul>
<h3>������</h3>
<ul>
<li><img src="edit.png"> ������ �� JOSM Remote Control</li>
</ul>

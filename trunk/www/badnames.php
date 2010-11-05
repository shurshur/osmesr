<?
  require_once("config.php");
  require_once("lib.php");
  Header("Content-Type: text/html; charset=$site_charset\n");

dbconn();

?>
<style>a { text-decoration: none; }</style>
<table border=1 cellspacing=0>
<tr>
 <td>ЕСР</td>
 <td>Экспресс</td>
 <td>Название</td>
 <td>РЖД</td>
 <td>УЗ</td>
 <td>ТР4к2</td>
 <td>ТР4к1</td>
 <td>ЯР</td>
</tr>

<?
$fields = array("express_code","name","name_rzd0","name_rwua","name_tr4k2","name_tr4k1","name_yarasp");
$res = mysql_query("SELECT * FROM stations WHERE fixed<1");
while($row = mysql_fetch_assoc($res)) {
  print "<tr><td><a href=./esr:".$row["esr"].">".$row["esr"]."</a></td>";
  foreach($fields as $field) {
    $tmp = $row[$field];
    if(!$tmp)
      $tmp = "&nbsp;";
    print "<td>".$tmp."</td>";
  }
  print "</tr>\n";
}

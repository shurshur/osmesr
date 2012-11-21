<?
require_once("config.php");
require_once("lib.php");

$q = isset($_REQUEST["q"])?trim($_REQUEST["q"]):null;
$r = isset($_GET["r"])?trim($_GET["r"]):null;

if (!$q) {
  Header("Location: ./");
  exit;
}

if (!$r && $q) {
  Header("Location: ./search:$q");
  exit;
}

if (mb_strlen($q, $site_charset) < 3) {
  print "Укажите не менее трёх символов";
  exit;
}

dbconn();

$rc = null;

if($q) {

  $filter = null;

  if (preg_match('/^[0-9]{5,6}$/',$q)) {
    $filter = "a.esr LIKE '".$q."%'";
  } elseif (preg_match('/^[0-9]{7}$/',$q)) {
    $filter = "a.express_code LIKE '".$q."%'";
  } else {
    $cols = array("a.name","a.name_rzd0","a.name_tr4k1","a.name_tr4k2","a.name_rwua","a.name_yarasp");
    $filter = array();
    foreach ($cols as $col) $filter[] = "($col LIKE '%".mysql_real_escape_string($q)."%')";
    $filter = implode(" OR ", $filter);
  }

  $query = "
    SELECT
      esr,
      a.name,
      regions.name,
      station_types.name,
      a.station_type_id AS stid
    FROM
      stations AS a
      LEFT JOIN regions ON a.region_id = regions.id
      LEFT JOIN station_types ON a.station_type_id = station_types.id
    WHERE
      ($filter)
      AND dup_esr=''
  ";
  if (preg_match('/^[0-9]{5,6}$/',$q))
  $query.= "
  UNION
    SELECT
      a.esr,
      a.name,
      regions.name,
      station_types.name,
      a.station_type_id AS stid
    FROM
      stations AS a
      LEFT JOIN stations AS b ON a.esr=b.dup_esr
      LEFT JOIN regions ON a.region_id = regions.id
      LEFT JOIN station_types ON a.station_type_id = station_types.id
    WHERE
      b.esr LIKE '$q%'
  ";
  $query.= "ORDER BY stid ASC";
  #print $query;

  $res = mysql_query($query);

  if (!$res) {
    print "<p>Query error: ".mysql_error()."</p>";
    exit;
  }

  $rc = mysql_num_rows($res);

  if ($rc == 1) {
    $row = mysql_fetch_row($res);
    Header("Location: ./esr:".$row[0]);
    exit;
  }
}

if (!$q) $q = "";

  Header("Content-Type: text/html; charset=$site_charset\n");
?>
<style>a { text-decoration: none; }</style>
<form action="search" method="post">
Поиск: <input type=text name=q value="<?print $q;?>" size=32>
<input type=submit value=Поиск>
</form>
<?

if (!isset($rc)) exit;

if ($rc < 1) {
  print "<p>Ничего не найдено</p>";
  exit;
}

if ($rc > 1) {
  if ($rc > 100) 
    print "<p>Найдено результатов: $rc, показаны первые 100.</p>\n";
  else
    print "<p>Найдено результатов: $rc.</p>\n";
  print "<ol>\n";

  $i = 0;
  while ($row = mysql_fetch_row($res)) {
    $i ++;
    if ($i > 100) break;
    print "<li><a href=./esr:".$row[0].">".$row[0].": ".$row[1]."</a> (".$row[2].")</li>";
  }

  print "</ol>\n";
}

?>


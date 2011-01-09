<?

function dbconn() {
  include("config.php");
  if (!mysql_connect($mysql_host, $mysql_login, $mysql_password)) 
    die ("Couldn't connect to database: ".mysql_error()."\n");
  if (!mysql_select_db($mysql_db))
    die ("Couldn't connect to database: ".mysql_error()."\n");
  @mysql_query("SET NAMES $mysql_charset");
}

function osmdataurl($type,$id,$name,$lat,$lon,$railway="station") {
  $types = array(
    0 => "node",
    1 => "way",
    2 => "relation"
  );

  $link = "";
  if($id<0) { $id = -$id; $type = 2; }
  if($name == "" || !isset($name)) $name = "(без названия)";
  if($type == 0 && $lat>0 && $lon>0) {
    $left = $lon-0.002;
    $right = $lon+0.002;
    $bottom = $lat-0.0012;
    $top = $lat+0.0012;
    $url = "http://127.0.0.1:8111/load_and_zoom?left=$left&right=$right&top=$top&bottom=$bottom&select=node$id";
    $url = preg_replace("/,/",".",$url);
    $link = "&nbsp;<a href=\"$url\"><img border=0 src=\"edit.png\"/></a>";
  } elseif ($type == 1) {
    $url = "http://127.0.0.1:8111/import?url=http://www.openstreetmap.org/api/0.6/way/$id/full";
    $link = "&nbsp;<a href=\"$url\"><img border=0 src=\"edit.png\"/></a>";
  } elseif ($type == 2) {
    $url = "http://127.0.0.1:8111/import?url=http://www.openstreetmap.org/api/0.6/relation/$id/full";
    $link = "&nbsp;<a href=\"$url\"><img border=0 src=\"edit.png\"/></a>";
  }
  return "<img src=\"".$types[$type].".png\"><img src=\"$railway.png\">&nbsp;<a href=\"http://www.openstreetmap.org/browse/".$types[$type]."/$id\">".$name."</a>$link\n";
}

?>

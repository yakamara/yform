<?php

$map_width = rex_request("map_width","int",920);
$map_height = rex_request("map_height","int",500);

$table = "REX_VALUE[1]";
$pos_lng = "REX_VALUE[2]";
$pos_lat = "REX_VALUE[3]";
$fields = "REX_VALUE[4]";
$zip_field = "REX_VALUE[19]";

$zip_table = "REX_VALUE[8]";
$zip_fields = explode(",","REX_VALUE[9]");

$vt_fields = "REX_VALUE[5]";
$where = "REX_VALUE[6]";

$marker_icon_normal = 'REX_MEDIA[1]';
$marker_icon_active = 'REX_MEDIA[2]';

$sidebar_view = 'REX_VALUE[7]';
$sidebar_view = str_replace('<br />','',$sidebar_view);
$sidebar_view = htmlspecialchars_decode($sidebar_view, ENT_QUOTES);

$map_view = 'REX_VALUE[10]';
$map_view = str_replace('<br />','',$map_view);
$map_view = htmlspecialchars_decode($map_view, ENT_QUOTES);

$print_view = 'REX_VALUE[11]';
$print_view = str_replace('<br />','',$print_view);
$print_view = htmlspecialchars_decode($print_view, ENT_QUOTES);

if($zip_table != "") {
    include $REX["INCLUDE_PATH"].'/addons/yform/plugins/geo/module/rex_geo_map_zip.php';
} else {
    include $REX["INCLUDE_PATH"].'/addons/yform/plugins/geo/module/rex_geo_map.php';
}

?>
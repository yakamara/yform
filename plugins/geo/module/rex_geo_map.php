<?php

/*
$table = "REX_VALUE[1]";
$pos_lng = "REX_VALUE[2]";
$pos_lat = "REX_VALUE[3]";
$vt_fields = "REX_VALUE[4]";
$fields = "REX_VALUE[5]";
$where = "REX_VALUE[6]";
$view = str_replace("<br />","","REX_VALUE[7]");
$view = str_replace("\n","",html_entity_decode($view));
$view = str_replace("\r","",$view);
*/

$searchall = rex_request('searchall', 'int', 0);
$page_size = 50;

// Data as json
$rex_geo_func = rex_request('rex_geo_func', 'string');
switch ($rex_geo_func) {
    case 'datalist':
        ob_end_clean();
        ob_end_clean();

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        $geo_search_text = rex_request('geo_search_text', 'string');
        $geo_search_page = rex_request('geo_search_page', 'int');
        $geo_search_page_size = rex_request('geo_search_page_size', 'int', 50);
        if ($geo_search_page_size < 0 or $geo_search_page_size > 200) {
            $geo_search_page_size = 50;
        }

        $geo_bounds_top = rex_request('geo_bounds_top', 'string');
        $geo_bounds_right = rex_request('geo_bounds_right', 'string');
        $geo_bounds_bottom = rex_request('geo_bounds_bottom', 'string');
        $geo_bounds_left = rex_request('geo_bounds_left', 'string');
        $sql_pos_add = ' ' . $pos_lng . '<>"" and ' . $pos_lat . '<>"" ';
        if ($geo_bounds_top != '' && $geo_bounds_bottom != '' && $geo_bounds_left != '' && $geo_bounds_right != '') {
            $sql_pos_add = '
                (' . $pos_lng . '>' . $geo_bounds_left . ' and ' . $pos_lng . '<' . $geo_bounds_right . ')
                and (' . $pos_lat . '<' . $geo_bounds_top . ' and ' . $pos_lat . '>' . $geo_bounds_bottom . ')
            ';
        }

        $sql_where = '';
        if ($where != '') {
            $sql_where = ' AND (' . $where . ') ';
        }

        $sql_vt_add = '';
        if ($geo_search_text != '') {
            $vtf = array();
            foreach (explode(',', $vt_fields) as $f) {
                $vtf[] = '(' . trim($f) . ' LIKE "%' . mysql_real_escape_string(trim($geo_search_text)) . '%"  )';
            }

            $sql_vt_add = ' and ( ' . implode(' OR ', $vtf) . ') ';
        }

        if ($geo_search_page < 0) {
            $geo_search_page = 0;
        }
        $sql_limit_from = ($geo_search_page * $geo_search_page_size);
        $sql_limit_to = (($geo_search_page + 1) * $geo_search_page_size) + 1;
        $sql_limit = ' order by rand(20)  LIMIT ' . $sql_limit_from . ',' . $sql_limit_to;

        $gd = rex_sql::factory();
        // $gd->debugsql = 1;
        $gd->setQuery('select ' . $fields . ',' . $pos_lng . ' as lng,' . $pos_lat . ' as lat from ' . $table . ' where ' . $sql_pos_add . $sql_where . $sql_vt_add . $sql_limit);
        echo json_encode($gd->getArray());
        exit;
        break;
}

?>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="/files/addons/yform/plugins/geo/geo.js"></script>
<link rel="stylesheet" type="text/css" href="/files/addons/yform/plugins/geo/geo.css" />

<div id="rex-googlemap" style="width:900px; height:500px;"></div>

<script type="text/javascript">

jQuery(document).ready(function(){

    var map_options = {
        div_id: "rex-googlemap",
        dataUrl: "<?php echo rex_getUrl($REX['ARTICLE_ID'], '', array('rex_geo_func' => 'datalist')); ?>",
        page_size: <?php echo $page_size; ?>,
        page_loading: '<div class="rex-geo-loading"></div>',
        sidebar_view: '<?php echo $view; ?>',
        print_view: '<?php echo $print_view; ?>',
        map_view: '<?php echo $map_view; ?>',
        fulltext: 1,
        zoom:6,
        marker_icon_normal: "/files/addons/yform/plugins/geo/icon_normal.png",
        marker_icon_active: "/files/addons/yform/plugins/geo/icon_active.png",
    };

    map_explorer = new rex_yform_geomap(map_options); //
    map_explorer.initialize();

});

</script>

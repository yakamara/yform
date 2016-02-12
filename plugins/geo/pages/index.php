<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform_geo'));

$table_name = rex_request('table_name','string');
$table = NULL;

$tables = rex_yform_manager_table::getAll();
$geo_tables = [];
foreach($tables as $i_table) {
    $fields = $i_table->getValueFields(array('type_name' => 'google_geocode'));
    if (count($fields) > 0) {
        if ($table_name == $i_table->getTableName()) {
            $table = $i_table;
        }
        $geo_tables[] = $i_table;
    }
}

if (!$table) {

    echo '<ul>';
    foreach($geo_tables as $g_table) {

        echo '<li><a href="index.php?page=yform/geo/index/&table_name='.$g_table->getTableName().'">'.$g_table->getTableName().'</a></li>';

    }
    echo '</ul>';

} else {

    $func = rex_request('geo_func', 'string');
    $field = rex_request('geo_field', 'string');

    $gd = rex_sql::factory();

    if ($func == 'get_data') {
        $data = array();
        ob_end_clean();
        if (array_key_exists($field, $fields)) {
            $address_fields = explode(',', $fields[$field]['address']);
            $fs = array();
            foreach ($address_fields as $f) {
                $fs[] = $gd->escapeIdentifier(trim($f));
            }
            $concat = 'CONCAT(' . implode(' , ",", ', $fs) . ') as address';

            $pos_fields = explode(',', $fields[$field]['position']);
            if (count($pos_fields) == 2) {
                $pos_lat = $pos_fields[0];
                $pos_lng = $pos_fields[1];
                // $gd->setDebug();
                $gd->setQuery('select id, ' . $concat . ' from ' . $table['table_name'] . ' where ' . $pos_lng . '="" or ' . $pos_lng . ' IS NULL or ' . $pos_lat . '="" or ' . $pos_lat . ' IS NULL LIMIT 200');
                $data = ($gd->getArray());
            }
        }
        echo json_encode($data);
        exit;

    } elseif ($func == 'save_data') {
        ob_end_clean();
        $data = '0';
        if (array_key_exists($field, $fields)) {
            $data_lng = rex_request('geo_lng', 'string');
            $data_lat = rex_request('geo_lat', 'string');
            $data_id = rex_request('geo_id', 'int', 0);
            $pos_fields = explode(',', $fields[$field]['position']);
            if (count($pos_fields) == 2) {
                $pos_lat = $pos_fields[0];
                $pos_lng = $pos_fields[1];
                $gd = rex_sql::factory();
                $gd->setQuery('select id, ' . $gd->escapeIdentifier($pos_lat) . ', ' . $gd->escapeIdentifier($pos_lng) . ' from ' . $table['table_name'] . ' where id = ' . $gd->escape($data_id) . '');
                if ($gd->getRows() == 1 && $data_lng != '' && $data_lat != '') {
                    $sd = rex_sql::factory();
                    $sd->setTable($table['table_name']);
                    $sd->setWhere('id=' . $data_id);
                    $sd->setValue($pos_lat, $data_lat);
                    $sd->setValue($pos_lng, $data_lng);
                    $sd->update();
                    $data = '1';
                }
            }
        }
        echo $data;
        exit;
    }

    echo '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>';

    echo '<script type="text/javascript">
    <!--

    var data = "";
    var table = "";
    var field = "";
    var geocoder = "";
    var data_counter = 0;
    var data_running = 0;
    var data_next = 0;

    function xform_geo_updates(tablem,fieldm)
    {
        if(data_running == 1) return false;
        data_running = 1;
        table = tablem;
        field = fieldm;
        var currentTime = new Date();
        link = "index.php?page=yform/geo/index&table_name="+table+"&geo_func=get_data&geo_field="+field+"&nocache="+currentTime.getTime();
        geocoder = new google.maps.Geocoder();
        jQuery.ajax({
            url: link,
            dataType: "json",
            success: function(datam){
                data = datam;
                data_counter = 0;
                xform_geo_update();
            },
            error: function() {
                alert("error loading "+link);
            }
        });

    }

    function xform_geo_update()
    {
        if(data.length == data_counter) {
            data_running = 0;
            return false;
        }

        var address = data[data_counter]["address"];
        var data_id = data[data_counter]["id"];

        geocoder.geocode( { "address": address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
                    lat = results[0].geometry.location.lat();
                    lng = results[0].geometry.location.lng();
                    data_link = "index.php?page=yform/geo/index&table_name="+table+"&geo_func=save_data&geo_field="+field+"&geo_lng="+lng+"&geo_lat="+lat+"&geo_id="+data_id;
                    jQuery.ajax({
                        url: data_link,
                        success: function(data_status){
                            if(data_status == "1") {
                                jQuery("#xform_geo_count_"+field).html(jQuery("#xform_geo_count_"+field).html()+". ");
                                data_next = "1";
                            }else {
                                // alert("data status" + data_status);
                            }
                        }
                    });
                }else {
                    // no result found
                    // alert("no result found: "+status);
                }
            }else {
                // status = ZERO_RESULTS, QUERY_LIMIT
                // alert("not possible: "+status)
            }
        });

        data_counter = data_counter + 1;

        if(data_next == "0") {
            jQuery("#xform_geo_count_"+field).html(jQuery("#xform_geo_count_"+field).html()+"<a href=\"index.php?page=yform/manager/data_edit&table_name="+table+"&data_id="+data_id+"&func=edit&start=\">Geocoding not possible, try manually [id=\""+data_id+"\"]</a>");
            // return false;
        }
        setTimeout("xform_geo_update()",1000);

    }

    -->
    </script>';

    ?><div class="rex-addon-output"><h2 class="rex-hl2">PlugIn: Google Geo Plugin</h2><div class="rex-addon-content"><p class="rex-tx1" style="margin-bottom:0">Geotagging der Inhalte (Beschränkt auf 1000 Datensaetze am Tag, ansonsten kann man von Google gesperrt werden. Bitte deswegen darauf achten, dass diese Funktion angemessen verwendet wird. Standard sind 200 Datensätze auf einen Schlag. Jeder "." der nach der Ausführung auftaucht ist ein Datensatz der aktualisiert wurde). Die Daten müssen, aufgrund von Lizenzen, über die Googlemap verwendet werden.</p><?php

    foreach ($fields as $k => $v) {
        echo '<p class="rex-button" style="margin-bottom:0"><br /><a class="rex-button" href="javascript:xform_geo_updates(\'' . $table['table_name'] . '\',\'' . $k . '\')">Google Geotagging starten</a> &nbsp;Hiermit werden alle Datensätze anhand des Felder "' . $k . '" nach fehlenden Geopositionen durchsucht und neu gesetzt. <br /><br />[<span id="xform_geo_count_' . $k . '"></span>]</p>';
    }

    echo '</div></div>';

}
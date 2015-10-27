<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

/** @type rex_yform_manager_table $table */
$table = $params['table'];

// ist feld google_geocode vorhanden ?
$fields = $table->getValueFields(array('type_name' => 'google_geocode'));

if (count($fields) > 0) {

    $func = rex_request('geo_func', 'string');
    $field = rex_request('geo_field', 'string');
    if ($func == 'get_data') {
        $data = array();
        ob_end_clean();
        if (array_key_exists($field, $fields)) {
            $address_fields = explode(',', $fields[$field]['address']);
            $fs = array();
            foreach ($address_fields as $f) {
                $fs[] = '`' . mysql_real_escape_string(trim($f)) . '`';
            }
            $concat = 'CONCAT(' . implode(' , ",", ', $fs) . ') as address';

            $pos_fields = explode(',', $fields[$field]['position']);
            if (count($pos_fields) == 2) {
                $pos_lat = $pos_fields[0];
                $pos_lng = $pos_fields[1];

                $gd = rex_sql::factory();
                // $gd->debugsql = 1;
                $gd->setQuery('select id, ' . $concat . ' from ' . $table['table_name'] . ' where ' . $pos_lng . '="" or ' . $pos_lng . ' IS NULL or ' . $pos_lat . '="" or ' . $pos_lat . ' IS NULL LIMIT 200'); // 1000
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
                $gd->setQuery('select id, `' . mysql_real_escape_string($pos_lat) . '`, `' . mysql_real_escape_string($pos_lng) . '` from ' . $table['table_name'] . ' where id=' . $data_id . '');
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

    function yform_geo_updates(tablem,fieldm)
    {
        if(data_running == 1) return false;
        data_running = 1;
        table = tablem;
        field = fieldm;
        var currentTime = new Date();
        link = "index.php?page=yform&subpage=manager&tripage=table_field&table_name="+table+"&geo_func=get_data&geo_field="+field+"&nocache="+currentTime.getTime();
        geocoder = new google.maps.Geocoder();
        jQuery.ajax({
            url: link,
            dataType: "json",
            success: function(datam){
                data = datam;
                data_counter = 0;
                yform_geo_update();
                },
                error: function() { alert("error loading "+link); }
        });

    }

    function yform_geo_update()
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
                    data_link = "index.php?page=yform&subpage=manager&tripage=table_field&table_name="+table+"&geo_func=save_data&geo_field="+field+"&geo_lng="+lng+"&geo_lat="+lat+"&geo_id="+data_id;
                    jQuery.ajax({
                        url: data_link,
                        success: function(data_status){
                            if(data_status == "1") {
                                jQuery("#yform_geo_count_"+field).html(jQuery("#yform_geo_count_"+field).html()+". ");
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
            jQuery("#yform_geo_count_"+field).html(jQuery("#yform_geo_count_"+field).html()+"<a href=\"/redaxo/index.php?page=yform&subpage=manager&tripage=data_edit&table_name="+table+"&data_id="+data_id+"&func=edit&start=\">Geocoding not possible, try manually [id=\""+data_id+"\"]</a>");
            // return false;
        }
        setTimeout("yform_geo_update()",1000);

    }



    -->
    </script>';

?><div class="rex-addon-output"><h2 class="rex-hl2">PlugIn: <a href="javascript:void(0);" onclick="jQuery('#rex-yform-geo-description').toggle()">Google Geo Plugin</a></h2><div id="rex-yform-geo-description" style="display:none;" class="rex-addon-content"><p class="rex-tx1" style="margin-bottom:0">Geotagging der Inhalte (Beschränkt auf 1000 Datensaetze am Tag, ansonsten kann man von Google gesperrt werden. Bitte deswegen darauf achten, dass diese Funktion angemessen verwendet wird. Standard sind 200 Datensätze auf einen Schlag. Jeder "." der nach der Ausführung auftaucht ist ein Datensatz der aktualisiert wurde). Die Daten müssen, aufgrund von Lizenzen, über die Googlemap verwendet werden.</p><?php

    foreach ($fields as $k => $v) {
        echo '<p class="rex-button" style="margin-bottom:0"><br /><a class="rex-button" href="javascript:void(0)" onclick="yform_geo_updates(\'' . $table['table_name'] . '\',\'' . $k . '\')">Google Geotagging</a> &nbsp;Hiermit werden alle Datensätze anhand des Felder "' . $k . '" nach fehlenden Geopositionen durchsucht und neu gesetzt. [<span id="yform_geo_count_' . $k . '"></span>]</p>';
    }

    echo '</div></div>';

}

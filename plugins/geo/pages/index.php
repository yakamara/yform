<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform_geo'));

$table_name = rex_request('table_name', 'string');
$table = null;

$tables = rex_yform_manager_table::getAll();
$geo_tables = [];
foreach ($tables as $i_table) {
    $fields = $i_table->getValueFields(['type_name' => 'google_geocode']);
    if (count($fields) > 0) {
        $geo_tables[] = $i_table;
        if ($table_name == $i_table->getTableName()) {
            $table = $i_table;
            break;
        }
    }    
}

if (count($geo_tables) == 0) {
    echo rex_view::info($this->i18n('geo_nogeotablefound'));
} elseif (!$table) {
    $content = [];
    foreach ($geo_tables as $g_table) {
        $content[] = '<a href="index.php?page=yform/geo/index/&table_name='.$g_table->getTableName().'" class="btn btn-setup">'.$g_table->getTableName().'</a>';
    }
    $content = '<p>'.implode('<br /><br />', $content).'</p>';

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('geo_choosetable'), false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
} elseif ($table) {
    $func = rex_request('geo_func', 'string');
    $field = rex_request('geo_field', 'string');
    
    $gd = rex_sql::factory();

    if ($func == 'get_data') {
        $data = [];
        ob_end_clean();
        if (array_key_exists($field, $fields)) {
            $address_fields = explode(',', $fields[$field]['address']);
            $fs = [];
            foreach ($address_fields as $f) {
                $fs[] = $gd->escapeIdentifier(trim($f));
            }
            $concat = 'CONCAT(' . implode(' , ",", ', $fs) . ') as address';

            $pos_fields = explode(',', $fields[$field]['position']); // das Element position gibt es nicht
            $pos_field = $fields[$field]['name'];
            if (count($pos_fields) == 2) {
                $pos_lat = $pos_fields[0];
                $pos_lng = $pos_fields[1];
                $gd->setQuery('select id, ' . $concat . ' from ' . $table['table_name'] . ' where ' . $pos_lng . '="" or ' . $pos_lng . ' IS NULL or ' . $pos_lat . '="" or ' . $pos_lat . ' IS NULL LIMIT 200');
                $data = ($gd->getArray());
            } elseif ($pos_field) {
                $gd->setQuery('select id, ' . $concat . ' from ' . $table['table_name'] . ' where ' . $pos_field . '="" or ' . $pos_field . ' IS NULL LIMIT 200');
                $data = ($gd->getArray());
            }
        }
        echo json_encode($data);
        exit;
    }
    if ($func == 'save_data') {
        ob_end_clean();
        $data = '0';
        if (array_key_exists($field, $fields)) {
            $data_lng = rex_request('geo_lng', 'string');
            $data_lat = rex_request('geo_lat', 'string');
            $data_id = rex_request('geo_id', 'int', 0);
            $pos_fields = explode(',', $fields[$field]['position']);
            $pos_field = $fields[$field]['name'];
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
            } elseif ($pos_field) {
                $gd->setQuery('select id from ' . $table['table_name'] . ' where id = ' . $gd->escape($data_id));
                if ($gd->getRows() == 1 && $data_lng != '' && $data_lat != '') {
                    $geopos = $data_lat.','.$data_lng;
                    $sd = rex_sql::factory();
                    $sd->setTable($table['table_name']);
                    $sd->setWhere('id=' . $data_id);
                    $sd->setValue($pos_field, $geopos);
                    $sd->update();
                    $data = '1';
                }                
            } 
        }
        echo $data;
        exit;
    }
    
    $geo_field_name = array_shift(array_keys($fields));    
    echo '<script type="text/javascript" src="//maps.google.com/maps/api/js?key='.$fields[$geo_field_name]['googleapikey'].'"></script>';

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
        link = "index.php?page=yform/geo/index&table_name="+table+"&geo_func=get_data&geo_field="+field+"&nocache="+currentTime.getTime();
        geocoder = new google.maps.Geocoder();
        jQuery.ajax({
            url: link,
            dataType: "json",
            success: function(datam){
                data = datam;
                data_counter = 0;
                yform_geo_update();
            },
            error: function() {
                alert("error loading "+link);
            }
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
                    data_link = "index.php?page=yform/geo/index&table_name="+table+"&geo_func=save_data&geo_field="+field+"&geo_lng="+lng+"&geo_lat="+lat+"&geo_id="+data_id;
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
            jQuery("#yform_geo_count_"+field).html(jQuery("#yform_geo_count_"+field).html()+"<a href=\"index.php?page=yform/manager/data_edit&table_name="+table+"&data_id="+data_id+"&func=edit&start=\">Geocoding not possible, try manually [id=\""+data_id+"\"]</a>");
            // return false;
        }
        setTimeout("yform_geo_update()",1000);

    }

    -->
    </script>';

    $content = '<p>'.$this->i18n('geo_tagginginfo').'</p>';
    foreach ($fields as $k => $v) {
        $content .= '<p><a class="btn btn-setup" href="javascript:yform_geo_updates(\'' . $table['table_name'] . '\',\'' . $k . '\')">Google Geotagging starten</a> &nbsp;Hiermit werden alle Datensätze anhand des Felder "' . $k . '" nach fehlenden Geopositionen durchsucht und neu gesetzt. <br /><br />[<span id="yform_geo_count_' . $k . '"></span>]</p>';
    }

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('geo_tagging', $table['table_name']), false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
}

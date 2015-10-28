<?php

$page = rex_request("page","string");
$searchall = rex_request("searchall","int",0);
$page_size = 200;
$assetsFolder = '/files/addons/yform/plugins/geo/';



if ($marker_icon_normal != "") {
  $marker_icon_normal = '/files/'.$marker_icon_normal;
} else {
  $marker_icon_normal = $assetsFolder.'images/i_icon.png';
}

if ($marker_icon_active != "") {
  $marker_icon_active = '/files/'.$marker_icon_active;
} else {
  $marker_icon_active = $assetsFolder.'images/i_icon_hover.png';
}

// Data as json
$rex_geo_func = rex_request("rex_geo_func","string");
switch($rex_geo_func) {

	case("plz"):

		ob_end_clean();
		ob_end_clean();
		
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		// header('Content-type: application/json');

		$return = array();
		$plz = rex_request("plz","string");

		if(strlen($plz) > 2) {
			$s = rex_sql::factory();
			$s->setQuery('select * from '.$zip_table.' where '.$zip_fields[0].' LIKE "'.mysql_real_escape_string($plz).'%"');

			foreach($s->getArray() as $p) {
				$return[] = array(
					"id" => $p[$zip_fields[0]],
					"label" => $p[$zip_fields[0]].' - '.$p[$zip_fields[3]].' / '.$p[$zip_fields[4]],
					"value" => $p[$zip_fields[3]],
					"lat" => $p[$zip_fields[1]],
					"lng" => $p[$zip_fields[2]]
				);
			}
			
		}

		echo json_encode($return);
		exit;

	case("city"):

		ob_end_clean();
		ob_end_clean();
		
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		$return = array();
		$city = rex_request("city","string");

		if(strlen($city) > 2) {
			$s = rex_sql::factory();
			// $s->debugsql = 1;
			$s->setQuery('select '.$zip_fields[0].', '.$zip_fields[3].', '.$zip_fields[4].', '.$zip_fields[1].', '.$zip_fields[2].' from '.$zip_table.' where '.$zip_fields[3].' LIKE "'.mysql_real_escape_string($city).'%" group by '.$zip_fields[3].','.$zip_fields[4].' LIMIT 10');

			foreach($s->getArray() as $p) {
				$return[] = array(
					"id" => $p[$zip_fields[0]],
					"label" => $p[$zip_fields[3]].' / '.$p[$zip_fields[4]],
					"value" => $p[$zip_fields[3]],
					"lat" => $p[$zip_fields[1]],
					"lng" => $p[$zip_fields[2]]

				);
			}
			
		}

		echo json_encode($return);
		exit;

	case("datalist"):
		ob_end_clean();
		ob_end_clean();
		
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		// header('Content-type: application/json');

		$geo_search_text = rex_request("geo_search_text","string");
		$geo_search_page = rex_request("geo_search_page","int");
		$geo_search_page_size = rex_request("geo_search_page_size","int",50);
		if($geo_search_page_size < 0 or $geo_search_page_size > 200) $geo_search_page_size = 50;
		
		$geo_bounds_top = rex_request("geo_bounds_top","string");
		$geo_bounds_right = rex_request("geo_bounds_right","string");
		$geo_bounds_bottom = rex_request("geo_bounds_bottom","string");
		$geo_bounds_left = rex_request("geo_bounds_left","string");
		
		$geo_search_plz = rex_request("geo_search_plz","string");
		
		$geo_search_zoom = rex_request("geo_search_zoom","int");

		$geo_center_lng = ($geo_bounds_right + $geo_bounds_left)/2;
		$geo_center_lat = ($geo_bounds_top + $geo_bounds_bottom)/2;
		
		$radius = 6368; // Erdradius (geozentrischer Mittelwert) in Km
		
		$rad_l = $geo_center_lng / 180 * M_PI;
		$rad_b = $geo_center_lat / 180 * M_PI;

    $distance_field = "";
    $distance_where = "";
    $distance_order = 'rand('.date('Ymd').')';

    /*
		if ($geo_search_zoom < 8) {
		} else {
			// Zoom 10 = Standard
			$umkreis = 130;
			$distance_field = "(".$radius." * SQRT(2*(1-cos(RADIANS(pos_lat)) * cos(".$rad_b.") * (sin(RADIANS(pos_lng)) *
	 sin(".$rad_l.") + cos(RADIANS(pos_lng)) * cos(".$rad_l.")) - sin(RADIANS(pos_lat)) * sin(".$rad_b.")))) AS Distance,";
			$distance_where = "".$radius." * SQRT(2*(1-cos(RADIANS(pos_lat)) *  cos(".$rad_b.") * (sin(RADIANS(pos_lng)) * sin(".$rad_l.") + cos(RADIANS(pos_lng)) * cos(".$rad_l.")) - sin(RADIANS(pos_lat)) * sin(".$rad_b."))) <= ".$umkreis." and ";
			$distance_order = 'Distance';
		}
    */

		$sql_pos_add = ' '.$pos_lng.'<>"" and '.$pos_lat.'<>"" ';
		
		if($searchall != 1)
			if($geo_bounds_top != "" && $geo_bounds_bottom != "" && $geo_bounds_left != "" && $geo_bounds_right != "") {
				$sql_pos_add = '
					('.$pos_lng.'>'.$geo_bounds_left.' and '.$pos_lng.'<'.$geo_bounds_right.')
					and ('.$pos_lat.'<'.$geo_bounds_top.' and '.$pos_lat.'>'.$geo_bounds_bottom.')
				';
			}

		$sql_where = "";
		if($where != "") {
			$sql_where = ' AND ('.$where.') ';
		}
		
		$sql_vt_add = '';

		if($geo_search_page<0) $geo_search_page = 0;
		$sql_limit_from = ($geo_search_page*$geo_search_page_size);
		$sql_limit_to = (($geo_search_page+1)*$geo_search_page_size)+1;
		$sql_limit = ' Order by '.$distance_order.' LIMIT '.$sql_limit_from.','.$sql_limit_to; // rand(20)

		$max_fields = 'min('.$pos_lng.') as min_lng,
						max('.$pos_lng.') as max_lng,
						min('.$pos_lat.') as min_lat,
						max('.$pos_lat.') as max_lat';

 		$sql = 'select '.$max_fields.' from '.$table.' where '.$sql_pos_add.$sql_where.$sql_vt_add;

		$gd = rex_sql::factory();
		// $gd->debugsql = 1;
		$gd->setQuery($sql);
		$bounds = $gd->getArray();


		$gd = rex_sql::factory();

		if ($geo_search_plz != "" && strlen($geo_search_plz) == 5) {
		    $sql_zip_where = $zip_field.'="'.mysql_real_escape_string($geo_search_plz ).'" and ';
    		$sql1 = 'select '.$distance_field.$fields.','.$pos_lng.' as lng,'.$pos_lat.' as lat from '.$table.' where '.$sql_zip_where .$distance_where.$sql_pos_add.$sql_where.$sql_vt_add.$sql_limit;

		    $sql_zip_where = $zip_field.'<>"'.mysql_real_escape_string($geo_search_plz ).'" and ';
    		$sql2 = 'select '.$distance_field.$fields.','.$pos_lng.' as lng,'.$pos_lat.' as lat from '.$table.' where '. $sql_zip_where.$distance_where.$sql_pos_add.$sql_where.$sql_vt_add.$sql_limit;
		
		    $sql = '
		SELECT * FROM 
(
  '.$sql1.'
) DUMMY_ALIAS1
UNION ALL
SELECT * FROM
( 
'.$sql2.'
) DUMMY_ALIAS2
		';
		
		} else {

    		$sql = 'select '.$distance_field.$fields.','.$pos_lng.' as lng,'.$pos_lat.' as lat from '.$table.' where '.$distance_where.$sql_pos_add.$sql_where.$sql_vt_add.$sql_limit;

    }

    // $gd->debugsql = 1;
		$gd->setQuery($sql);
		
		// ----- Markers & DIV

    class rex_var_yform_map extends rex_var
    {
        function getBEOutput(& $sql, $content)
        {

    
            $var = 'REX_DATA';
            $matches = $this->getVarParams($content, $var);
            foreach ($matches as $match) {
                list ($param_str, $args) = $match;
                //list ($field, $args) = $this->extractArg('field', $args, 0);
                $field = $args['field'];
                if($field != "") {
                    $varname = '$__rex_data'; // $varname = str_replace('"', '\"', $varname);
                    $value =  "";
    
                    $suffix = '';
                    if (isset($args['translate']) && $args['translate'] == "1" && $REX['CUR_CLANG'] >= 1) {
                        $suffix = '_' . $REX['CUR_CLANG'];
                        $value = rex_var::handleGlobalVarParams($varname, $args, $sql->getValue($field . $suffix));
                    }
                    if ($value == '') {
                        // Fallback, falls Feld fuer SPrache nciht gefuellt ist
                        $value = rex_var::handleGlobalVarParams($varname, $args, $sql->getValue($field));
                    }
                    $content = str_replace($var . '[' . $param_str . ']', $value, $content);
                }
            }
            return $content;
        }
    }
		
		class yform_simulate_sql {
		
		  var $vars = array();
      function 		 yform_simulate_sql ($data)
      {
          $this->vars = $data;
      } 

      function getValue($key)
      {
          return $this->vars[$key];
      }
		
		}
		
		$data = $gd->getArray();
		foreach($data as $data_key => $d) {

        $sql = new yform_simulate_sql($d);
    		$rex_var_yform_map = new rex_var_yform_map;
        $d_map_view = $rex_var_yform_map->getFEOutput($sql, $map_view);
        $d_sidebar_view = $rex_var_yform_map->getFEOutput($sql, $sidebar_view);
        $d_print_view = $rex_var_yform_map->getFEOutput($sql, $print_view);

        $data[$data_key]['map_view'] = $d_map_view;
    	  $data[$data_key]['print_view'] = $d_print_view;
    	  $data[$data_key]['sidebar_view'] = $d_sidebar_view;


        // preg_replace um Umbrüche aus dem Html zu entfernen
        // nl2br um Umbrüche im Datensatz zu erzwingen
//         $c_markup_marker = nl2br($rex_var_qsgm->getFEOutput($sql, preg_replace('/[\n\r\f\t]/', '', $c_markup_marker)));
    
        
    
    }
		
		
		
		/*
		
		exit;
		
		$data = $gd->getArray();
		foreach($data as $data_key => $d) {
		
		  $d_map_view = $map_view;
		  $d_sidebar_view = $sidebar_view;
		  $d_print_view = $print_view;
		  foreach($d as $k => $v) {
  	    $d_map_view = str_replace("###".$k."###",htmlspecialchars($v), $d_map_view);
  	    $d_map_view = str_replace("***".$k."***",urlencode($v), $d_map_view);
  	    $d_map_view = str_replace("---".$k."---",$v, $d_map_view);
  	    $d_sidebar_view = str_replace("###".$k."###",htmlspecialchars($v), $d_sidebar_view);
  	    $d_sidebar_view = str_replace("***".$k."***",urlencode($v), $d_sidebar_view);
  	    $d_sidebar_view = str_replace("---".$k."---",$v, $d_sidebar_view);
  	    $d_print_view = str_replace("###".$k."###",htmlspecialchars($v), $d_print_view);
  	    $d_print_view = str_replace("***".$k."***",urlencode($v), $d_print_view);
  	    $d_print_view = str_replace("---".$k."---",$v, $d_print_view);
  	  }	
  	  $data[$data_key]['map_view'] = $d_map_view;
  	  $data[$data_key]['print_view'] = $d_print_view;
  	  $data[$data_key]['sidebar_view'] = $d_sidebar_view;
		}
		*/
		
		
		$output = array();
		$output["data"] = $data;
		$output["bounds"] = $bounds;
		
		echo json_encode($output);
		
		exit;
		break;
}

?>

<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>

<link rel="stylesheet" href="<?php echo $assetsFolder; ?>js/jquery_ui.css" type="text/css" media="all" />
<script type="text/javascript" src="<?php echo $assetsFolder; ?>js/geo_zip.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $assetsFolder; ?>css/geo_zip.css" />

<?php

echo '<div id="rex-googlemap" style="height:'.$map_height.'px;"></div>';
// style="< ?php echo 'width:'.$map_width.'px;'; ? > height:< ?php echo $map_height; ? >px;"

?>

<script type="text/javascript">

jQuery(document).ready(function(){

	var map_options = {
		div_id: "rex-googlemap",
		dataUrl: "<?php echo rex_getUrl($REX["ARTICLE_ID"],'',array('rex_geo_func' => 'datalist'),'&'); ?>",
		plzUrl: "<?php echo rex_getUrl($REX["ARTICLE_ID"],'',array('rex_geo_func' => 'plz'),'&'); ?>",
		cityUrl: "<?php echo rex_getUrl($REX["ARTICLE_ID"],'',array('rex_geo_func' => 'city'),'&'); ?>",
		page_size: <?php echo $page_size; ?>,
		page_loading: '<div class="rex-geo-loading"></div>',
		fulltext: 1,
		zoom: 6,
    marker_icon_normal: "<?php echo $marker_icon_normal; ?>",
    marker_icon_active: "<?php echo $marker_icon_active; ?>",
	};

	map_explorer = new rex_yform_geomap(map_options); //
	map_explorer.initialize();


});
	    
</script>
<?php

//    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&amp;"></script>
//    <script src="/assets/vendor/jquery.min.js"></script>
//    <script src="/assets/vendor/jquery.quicksand.js"></script>
//    <script src="/assets/vendor/infobox.js"></script>

$slice_id = 'REX_SLICE_ID';

$table_data = 'REX_VALUE[1]';
$table_cat_field = 'REX_VALUE[2]';
$table_cat = 'REX_VALUE[3]';

$markup_div = 'REX_VALUE[5]';
$markup_div = htmlspecialchars_decode($markup_div, ENT_QUOTES);
$markup_div = str_replace('<br />', '', $markup_div);

$markup_marker = 'REX_VALUE[6]';
$markup_marker = htmlspecialchars_decode($markup_marker, ENT_QUOTES);
$markup_marker = str_replace('<br />', '', $markup_marker);

$max_zoom = (int) 'REX_VALUE[4]';
if ($max_zoom < 1 || $max_zoom > 16) {
  $max_zoom = 8;
}

$marker_image_normal = '/files/REX_MEDIA[1]';
$marker_image_hover = '/files/REX_MEDIA[2]';

?><?php

// $slice_id;
// $markup_div
// $markup_marker
// $marker_image_normal
// $marker_image_hover

$id_selector = 'qsgm-' . $slice_id;
$map_selector = 'google-contact-map-' . $slice_id;

// ----- OUTPUT START

echo '<div class="googlemap"><div id="' . $map_selector . '" style="width: 100%; height: 380px;"></div></div>';
echo '<section class="content" id="' . $id_selector . '">';
// ----- Filter Navi

$sql = rex_sql::factory();
$sql->setQuery('select * from ' . mysql_real_escape_string($table_cat));
$navi = array();
$navi[] = '<li data-value="region-all"><a class="active" href="javascript:void(0);">###allregions###</a></li>';
foreach ($sql->getArray() as $r) {
    $navi[] = '<li data-value="region-' . $r['id'] . '"><a href="javascript:void(0);">' . $r['name'] . '</a></li>';
}
echo '<ul class="filter-list">' . implode('', $navi) . '</ul>';



// ----- Markers & DIV

if (!class_exists('rex_var_qsgm extends')) {
    class rex_var_qsgm extends rex_var
    {
        function getBEOutput(& $sql, $content)
        {
            $var = 'REX_DATA';
            $matches = $this->getVarParams($content, $var);
            foreach ($matches as $match) {
                list ($param_str, $args) = $match;
                list ($field, $args) = $this->extractArg('field', $args, 0);
                if ($field != '') {
                    $varname = '$__rex_data'; // $varname = str_replace('"', '\"', $varname);
                    $value =  '';
                    $value = rex_var::handleGlobalVarParams($varname, $args, $sql->getValue($field));
                    $content = str_replace($var . '[' . $param_str . ']', $value, $content);
                }
            }
            return $content;
        }
    }
}





$markers_map = array();
$markers_div = array();

$sql = rex_sql::factory();
$sql->setQuery('SELECT * FROM ' . mysql_real_escape_string($table_data));

for ($i = 0; $i < $sql->getRows(); $i++) {

    $id = $sql->getValue('id');
    $region = $sql->getValue($table_cat_field);

    $c_markup_div = $markup_div;
    $c_markup_marker = $markup_marker;

    $rex_var_qsgm = new rex_var_qsgm;
    $c_markup_div = $rex_var_qsgm->getFEOutput($sql, $c_markup_div);
    // preg_replace um Umbrüche aus dem Html zu entfernen
    // nl2br um Umbrüche im Datensatz zu erzwingen
    $c_markup_marker = nl2br($rex_var_qsgm->getFEOutput($sql, preg_replace('/[\n\r\f\t]/', '', $c_markup_marker)));

    $markers_div[]  = '<li data-id="id-' . $id . '" data-type="region-' . $region . '">' . $c_markup_div . '</li>';

    $markers_map[] = '
markers["' . $id . '"] = new Object;
markers["' . $id . '"]["lng"] = "' . $sql->getValue('pos_lng') . '";
markers["' . $id . '"]["lat"] = "' . $sql->getValue('pos_lat') . '";
markers["' . $id . '"]["link"] = "#";
markers["' . $id . '"]["marker"] = "' . html_entity_decode(preg_replace('/[\n\r\f\t]/', '', addslashes($c_markup_marker))) . '";
markers["' . $id . '"]["address"] = "";
    ';


    $sql->next();
}

echo '<ul class="blocks3units marker-list">' . implode('', $markers_div) . '</ul>';

echo '<script type="text/javascript">
var markers = [];
';
echo implode('', $markers_map);
echo '</script>';

?></section>



<script language="JavaScript">

    $(window).load(function(){

        var $itemsHolder = $('#<?php echo $id_selector; ?> .marker-list');
        var $itemsClone = $itemsHolder.clone();
        var $filterClass = "";

        $('#<?php echo $id_selector; ?> .filter-list li').click(function(e) {

            $(this).parent().find("li a").each(function(){
              $(this).removeClass("active");
            })

            $(this).find("a").addClass("active");

            $filterClass = $(this).attr('data-value');
            if($filterClass == 'region-all'){
                var $filters = $itemsClone.find('li');
            } else {
                var $filters = $itemsClone.find('li[data-type='+ $filterClass +']');
            }

            $itemsHolder.quicksand($filters, {
                duration: 1000
                /*,easing: 'easeInOutQuad'*/
            },function() {
                initialize_hover<?php echo $slice_id; ?>(0);

                $.each(markers, function(index, value) {
                    if (typeof value !== "undefined") {
                        markers[index].setVisible(false);
                    }
                });

                marker_counter = 0;
                bounds = new google.maps.LatLngBounds();
                $.each($filters, function(index, value) {
                    var id = $(this).attr("data-id").replace("id-","");
                    markers[id].setVisible(true);
                    bounds.extend( markers[id].getPosition() );
                    marker_counter++;
                });

                if (marker_counter > 0) {
                  mapref.fitBounds(bounds);
                  var listener = google.maps.event.addListener(mapref, "idle", function() {
                  if (mapref.getZoom() > <?php echo $max_zoom; ?>) mapref.setZoom(<?php echo $max_zoom; ?>);
                      google.maps.event.removeListener(listener);
                  });
                }

            }
            );
        });

        var mapref;
        function initialize_referenzen_map<?php echo $slice_id; ?>() {

            var posLL = new google.maps.LatLng(50.13,8.67);
            var style = [
                {
                    featureType: "all",
                    elementType: "all",
                    stylers: [
                        { saturation: -100 }
                    ]
                }
            ];

            var mapOptions = {
                zoom: 12,
                scrollwheel: false,
                center: posLL,
                panControl: false,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.LEFT_BOTTOM
                },
                navigationControl: true,
                mapTypeControl: true,
                scaleControl: true,
                draggable: true,
                mapTypeControlOptions: {
                    mapTypeIds: [google.maps.MapTypeId.ROADMAP, 'gYMap']
                }
            };

            mapref = new google.maps.Map(document.getElementById("<?php echo $map_selector; ?>"), mapOptions);

            var image_normal = '<?php echo $marker_image_normal; ?>';
            var image_hover = '<?php echo $marker_image_hover; ?>';

            var image_normal = new google.maps.MarkerImage('<?php echo $marker_image_normal; ?>',
                  null,
                  new google.maps.Point(0,0)
              );

            var image_hover = new google.maps.MarkerImage('<?php echo $marker_image_hover; ?>',
                  null,
                  new google.maps.Point(0,0)
              );

            var mapType = new google.maps.StyledMapType(style, { name:"Grayscale" });
            mapref.mapTypes.set('gYMap', mapType);
            // mapref.setMapTypeId('gYMap');

            <?php

            // TODO: Marker Höhe auslesen und den Offset anpassen
            // TODO: Infowindow Höhe auslesen und enstprechend Offset setzen

            ?>

            var myOptions1 = {
                content: "",
                disableAutoPan: false,
                pixelOffset: new google.maps.Size(-130, 0), // -130, -255 / 260 190
                zIndex: null,
                boxStyle: {
                    background: "", opacity: 1, width: "260px" //, height: "240px"
                },
                closeBoxURL: "",
                isHidden: false
            };
            var infoWindow = new InfoBox(myOptions1);

            var onMarkerClick = function() {
                var marker = this;
                var latLng = marker.getPosition();
                infoWindow.setContent('' + marker.address + '');
                infoWindow.open(mapref, marker);
            };
            google.maps.event.addListener(mapref, 'click', function() {
                infoWindow.close();
            });

            bounds = new google.maps.LatLngBounds();
            $.each(markers, function(index, value) {
                if (typeof value !== "undefined") {
                    if (value.lat != "" && value.lng != "") {
                        var markerPos = new google.maps.LatLng(value.lat, value.lng);
                        markers[index] = new google.maps.Marker({
                            animation: google.maps.Animation.DROP,
                            map: mapref,
                            icon: image_normal,
                            position: markerPos,
                            title: value.address,
                            address: value.marker,
                            link: value.link
                        });
                        google.maps.event.addListener(markers[index], 'mouseover', function() {
                            markers[index].setIcon(image_hover);
                        });
                        google.maps.event.addListener(markers[index], 'mouseout', function() {
                            markers[index].setIcon(image_normal);
                        });
                        google.maps.event.addListener(markers[index], 'click', onMarkerClick);

                        bounds.extend( markers[index].getPosition() );
                    }
                }
            });

            mapref.fitBounds(bounds);

        }
        initialize_referenzen_map<?php echo $slice_id; ?>();
        initialize_hover<?php echo $slice_id; ?>(0);

        var init = 0;
        function initialize_hover<?php echo $slice_id; ?>(init) {
            $("#<?php echo $id_selector; ?> .marker-list li").hover(function() {
                var id = $(this).attr("data-id").replace("id-","");
                if (init != 1) {
                    markers[id].setAnimation(google.maps.Animation.BOUNCE);
                }

            },function() {
                var id = $(this).attr("data-id").replace("id-","");
                if (init != 1) {
                    markers[id].setAnimation(null);
                }
            });
        }

    });

</script>

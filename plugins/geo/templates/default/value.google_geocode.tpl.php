<?php if ($includeGoogleMaps): ?>
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>
<?php endif ?>
<script type="text/javascript">

<?php

$labelLat = '#yform-' . $this->params['form_name'] . '-'.$labelLat;
$labelLng = '#yform-' . $this->params['form_name'] . '-'.$labelLng;

?>

    var rex_geo_coder = function() {

        var myLatlng = new google.maps.LatLng(<?php echo $valueLat ?>, <?php echo $valueLng ?>);

        var myOptions = {
            zoom: 8,
            center: myLatlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        }

        var map = new google.maps.Map(document.getElementById("map_canvas<?php echo $this->getId() ?>"), myOptions);

        marker = new google.maps.Marker({
            position: myLatlng,
            map: map,
            draggable: true
        });

        google.maps.event.addListener(marker, "dragend", function() {
            rex_geo_updatePosition(marker.getPosition());
        });

        rex_geo_updatePosition = function(latLng) {
            jQuery("<?php echo $labelLat ?> input").val( latLng.lat() );
            jQuery("<?php echo $labelLng ?> input").val( latLng.lng() );
        }

        geocoder = new google.maps.Geocoder();

        rex_geo_getPosition = function() {

            fields = [];
            <?php 
            
            $i=0;
            foreach(explode(",",$address) as $adr) {
              echo "\n".'fields['.$i.'] = jQuery("#yform-' . $this->params['form_name'] . '-'.$adr.' input").val();';
              $i++;
            }
            
            ?>
            address = fields.join(",");

            geocoder.geocode( { "address": address }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
                        map.setCenter(results[0].geometry.location);
                        marker.setMap(null);
                        marker = new google.maps.Marker({
                            position: results[0].geometry.location,
                            map: map,
                            title: address,
                            draggable: true
                        });
                        google.maps.event.addListener(marker, "dragend", function() {
                            rex_geo_updatePosition(marker.getPosition());
                        });
                        rex_geo_updatePosition(marker.getPosition());

                    } else {
                        alert("No results found");

                    }
                } else {
                    alert("Geocode was not successful for the following reason: " + status);

                }
            });

        }

        rex_geo_resetPosition = function() {
            jQuery(function($){
                jQuery("<?php echo $labelLat ?> input").val("0");
                jQuery("<?php echo $labelLng ?> input").val("0");
            });

        }

    }

    jQuery(function($){
        rex_geo_coder<?php echo $this->getId() ?> = new rex_geo_coder();
    });

</script>

<div class="yform-element form_google_geocode <?php echo $this->getHTMLClass() ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="text <?php echo $this->getWarningClass() ?>" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getElement('label') ?></label>
    <p class="form_google_geocode">
        <a href="javascript:void(0);" onclick="rex_geo_getPosition()">Geodaten holen</a> |
        <a href="javascript:void(0);" onclick="rex_geo_resetPosition()">Geodaten nullen</a>
    </p>
    <div class="form_google_geocode_map" id="map_canvas<?php echo $this->getId() ?>" style="width:<?php echo $mapWidth ?>px; height:<?php echo $mapHeight ?>px">Google Map</div>
</div>

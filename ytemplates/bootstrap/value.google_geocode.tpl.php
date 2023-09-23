<?php

/**
 * @var rex_yform_value_google_geocode $this
 * @psalm-scope-this rex_yform_value_google_geocode
 */

$address ??= '';
$googleapikey ??= '';
$value ??= $this->getValue() ?? '';
$mapZoom ??= 5;
$mapWidth ??= 800;
$mapHeight ??= 600;

$FieldsAddress = [];
$LabelsAddress = explode(',', $address);

foreach ($this->params['values'] as $address_value) {
    if (in_array($address_value->getName(), $LabelsAddress)) {
        $FieldsAddress[] = '#' . $address_value->getFieldId();
    }
}

if ('' != $googleapikey) {
    echo '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=' . $googleapikey . '&sensor=false" nonce="' . rex_response::getNonce() . '"></script>';
} else {
    echo '<script type="text/javascript" src="//maps.google.com/maps/api/js?sensor=true" nonce="' . rex_response::getNonce() . '"></script>';
}

?>
<script type="text/javascript" nonce="<?= rex_response::getNonce() ?>">

    var rex_geo_coder = function() {

        jQuery("#<?= $this->getFieldId() ?>").on("change", function(){

        });

        jQuery("#<?= $this->getHTMLId('google') ?> .yform-google-btnbar .get-position").on("click", function(){
            rex_geo_getPosition();
        });

        jQuery("#<?= $this->getHTMLId('google') ?> .yform-google-btnbar .clear-position").on("click", function(){
            rex_geo_clearPosition();
        });

        var myLatlng = new google.maps.LatLng(<?= $value ?>);

        var myOptions = {
            zoom: <?= $mapZoom ?>,
            center: myLatlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        }

        var map = new google.maps.Map(document.getElementById("map_canvas<?= $this->getId() ?>"), myOptions);

        var marker = new google.maps.Marker({
            position: myLatlng,
            map: map,
            draggable: true
        });

        google.maps.event.addListener(marker, "dragend", function() {
            rex_geo_updatePosition(marker.getPosition());
        });

        rex_geo_updatePosition = function(latLng) {
            jQuery("#<?= $this->getFieldId() ?>").val( latLng.lat()+","+latLng.lng() );
            map.setCenter(latLng);
        }

        var geocoder = new google.maps.Geocoder();

        rex_geo_getPosition = function() {

            var fields = [];
            <?php

            $i = 0;
            foreach ($FieldsAddress as $adr) {
                echo "\n" . 'fields[' . $i . '] = jQuery("' . $adr . '").val();';
                ++$i;
            }

            ?>

            var address = fields.join(",");

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

        rex_geo_clearPosition = function() {

            jQuery("#<?= $this->getFieldId() ?>").val("0,0");
            var clearLatlng = new google.maps.LatLng(0, 0);
            marker.setMap(null);
            marker = new google.maps.Marker({
                position: clearLatlng,
                map: map,
                draggable: true
            });
            google.maps.event.addListener(marker, "dragend", function() {
                rex_geo_updatePosition(marker.getPosition());
            });
            rex_geo_updatePosition(marker.getPosition());

        }

    }

    jQuery(function($){
        rex_geo_coder<?= $this->getId() ?> = new rex_geo_coder();

    });

</script>

<?php

if ((string) (int) $mapWidth == (string) $mapWidth) {
    $mapWidth .= 'px';
}
if ((string) (int) $mapHeight == (string) $mapHeight) {
    $mapHeight .= 'px';
}

?>

<div class="<?= $this->getHTMLClass() ?>" id="<?= $this->getHTMLId('google') ?>">
    <label class="text <?= $this->getWarningClass() ?>"><?= $this->getElement('label') ?></label>
    <p class="yform-google-btnbar">
        <a class="get-position" href="javascript:void(0);"><?= rex_i18n::msg('yform_geo_get_position') ?></a> |
        <a class="clear-position" href="javascript:void(0);"><?= rex_i18n::msg('yform_geo_clear_position') ?></a>
    </p>
    <div class="form_google_geocode_map" id="map_canvas<?= $this->getId() ?>" style="
    <?php
    echo 'width: ' . $mapWidth . ';';
    echo 'height: ' . $mapHeight;
    ?>">Google Map</div>
</div>

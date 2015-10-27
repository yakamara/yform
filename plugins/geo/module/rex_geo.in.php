<?php

// TABLE SELECT
////////////////////////////////////////////////////////////////////////////////
$gc = rex_sql::factory();
$gc->setQuery('SHOW TABLES');
$tables = $gc->getArray();
$tbl_sel = new rex_select;
$tbl_sel->setName('VALUE[1]');
$tbl_sel->setSize(1);
$tbl_sel->addOption('Keine Tabelle ausgewählt', '');
foreach ($tables as $key => $value) {
  $tbl_sel->addOption(current($value), current($value));
}
$tbl_sel->setSelected('REX_VALUE[1]');
$tbl_sel = $tbl_sel->get();

$plz_tbl_sel = new rex_select;
$plz_tbl_sel->setName('VALUE[8]');
$plz_tbl_sel->setSize(1);
$plz_tbl_sel->addOption('Keine Tabelle ausgewählt', '');
foreach ($tables as $key => $value) {
  $plz_tbl_sel->addOption(current($value), current($value));
}
$plz_tbl_sel->setSelected('REX_VALUE[8]');
$plz_tbl_sel = $plz_tbl_sel->get();

?>

<table class="rex-table">
    <tr>
        <th colspan="2">yform GeoModul</th>
    </tr>
    <tr>
        <th>Quell-Tabelle</th>
        <td><?php echo $tbl_sel;?></td>
    </tr>
    <tr>
        <th>Lat Feld</th>
        <td><input type="text" name="VALUE[3]" value="REX_VALUE[3]" /><br /><i>Feld(name) der Quell-Tabelle welcher die Lat (latitude) Position enthält</i></td>
    </tr>
    <tr>
        <th>Lng Feld</th>
        <td><input type="text" name="VALUE[2]" value="REX_VALUE[2]" /><br /><i>Feld(name) der Quell-Tabelle welcher die Lng (longitude) Position enthält</i></td>
    </tr>
    <tr>
        <th>PLZ-Feld</th>
        <td><input type="text" name="VALUE[19]" value="REX_VALUE[19]" /></td>
    </tr>
    <tr>
        <th>PLZ-Tabelle</th>
        <td><?php echo $plz_tbl_sel;?><br /><i>optional</i></td>
    </tr>
    <tr>
        <th>PLZ Felder</th>
        <td><input type="text" name="VALUE[9]" value="REX_VALUE[9]" /><br /><i>Feldnamen kommasepariert [plz,lat,lng,city,state_code]</i></td>
    </tr>
    <tr>
        <th>Zu beziehende Felder</th>
        <td><input type="text" name="VALUE[4]" value="REX_VALUE[4]" /><br /><i>Feldnamen kommasepariert</i></td>
    </tr>
    <tr>
        <th>Volltextsuchfelder</th>
        <td><input type="text" name="VALUE[5]" value="REX_VALUE[5]" /><br /><i>Feldnamen kommasepariert</i></td>
    </tr>
    <tr>
        <th>WHERE condition</th>
        <td><input type="text" name="VALUE[6]" value="REX_VALUE[6]" /><br /><i>optional</i></td>
    </tr>
    <tr>
        <th>Markericon normal</th>
        <td>REX_MEDIA_BUTTON[1]</td>
    </tr>
    <tr>
        <th>Markericon active</th>
        <td>REX_MEDIA_BUTTON[2]</td>
    </tr>
    <tr>
        <th>Sidebar HTML</th>
        <td><textarea rows="6" name="VALUE[7]">REX_VALUE[7]</textarea><br /><i>Mit ###id### als Ersetzungen, ***id*** für urlencoded Ersetzungen</i></td>
    </tr>
    <tr>
        <th>Map HTML</th>
        <td><textarea rows="6" name="VALUE[10]">REX_VALUE[10]</textarea><br /><i>Mit ###id### als Ersetzungen, ***id*** für urlencoded Ersetzungen</i></td>
    </tr>
    <tr>
        <th>Druckversion HTML</th>
        <td><textarea rows="6" name="VALUE[11]">REX_VALUE[11]</textarea><br /><i>Mit ###id### als Ersetzungen, ***id*** für urlencoded Ersetzungen</i></td>
    </tr>
    <tr>
        <th colspan="2" style="text-align: right;"><i>yform GeoModul v1.1</i></th>
    </tr>
</table>
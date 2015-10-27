<!-- 99 - Googlemap mit Kontaktregionen -->

<table class="rex-table">

<tr>
<td>Datentabelle:</td>
<td><?php $tables = rex_sql::factory()->getArray('show tables;');
$select = new rex_select();
foreach ($tables as $table) {
    $select->addOption(current($table), current($table));
}
$select->setSize(1);
$cat_select = clone $select;

$select->setName('VALUE[1]');
$select->setSelected('REX_VALUE[1]');
echo $select->get();

?></td>
<td>Kategoriefeld:</td>
<td><input type="text" name="VALUE[2]" value="REX_VALUE[2]" style="width:100px" /></td>

</tr>



<tr>
<td>Kategorietabelle:</td>
<td><?php
$cat_select->setName('VALUE[3]');
$cat_select->setSelected('REX_VALUE[3]');
echo $cat_select->get();
?></td>

<td>Maximaler Zoom:</td>
<td><?php

$max_zoom = (int) 'REX_VALUE[4]';
if ($max_zoom < 1 || $max_zoom > 16) {
    $max_zoom = 8;
}

$zoom_select = new rex_select();
$zoom_select->setName('VALUE[4]');
$zoom_select->setSelected($max_zoom);

for ($zoom = 1; $zoom <= 16; $zoom++) {
    $zoom_select->addOption($zoom, $zoom);
}
$zoom_select->setSize(1);
echo $zoom_select->get();

?></td>
</tr>

<tr>
<td>Marker:</td>
<td colspan="3"><textarea name="VALUE[6]" style="width:500px; height:200px">REX_VALUE[6]</textarea></td>
</tr>

<tr>
<td>DIV:</td>
<td colspan="3"><textarea name="VALUE[5]" style="width:500px; height:200px">REX_VALUE[5]</textarea></td>
</tr>

<tr>
<td>Marker Normal:</td>
<td colspan="3">REX_MEDIA_BUTTON[1]</td>
</tr>

<tr>
<td>Marker Over:</td>
<td colspan="3">REX_MEDIA_BUTTON[2]</td>
</tr>

</table>

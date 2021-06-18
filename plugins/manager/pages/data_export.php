<?php

$rex_yform_filter = $rex_yform_filter ?? [];
$rex_yform_set = $rex_yform_set ?? [];
$searchObject = $searchObject ?? null;

$query = $this->getDataListQuery(array_merge($rex_yform_filter, $rex_yform_set), $searchObject, $this->table);

/** @var rex_list $list */
$list = rex_list::factory($query, $this->table->getListAmount());
$list->setColumnSortable('id');

$sortColumn = $list->getSortColumn();
if ('' != $sortColumn) {
    $sortType = $list->getSortType();
    $sql = rex_sql::factory();
    $sortColumn = $sql->escapeIdentifier($sortColumn);
    if (false === stripos($query, ' ORDER BY ')) {
        $query .= ' ORDER BY '.$sortColumn.' '.$sortType;
    } else {
        $query = preg_replace('/ORDER\sBY\s[^ ]*(\sasc|\sdesc)?/i', 'ORDER BY '.$sortColumn.' '.$sortType, $query);
    }
}

$g = rex_sql::factory();
$g->setQuery($query);
$dataset = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_TABLE_EXPORT', $g->getArray(), ['table' => $this->table]));

$fields = ['id' => '"id"'];
foreach ($this->table->getFields() as $field) {
    if ('value' == $field->getType() && 'none' != $field->getDatabaseFieldType()) {
        $fields[$field->getName()] = '"' . $field->getName() . '"';
    }
}

$exportDataset = [];
foreach ($dataset as $data) {
    $exportData = [];
    foreach ($fields as $fieldName => $fV) {
        $exportData[$fieldName] = '"' . str_replace(['"', "\n", "\r"], ['""', '', ''], $data[$fieldName]) . '"';
    }
    $exportDataset[] = implode(';', $exportData);
}

$fileContent = pack('CCC', 0xef, 0xbb, 0xbf);
$fileContent .= implode(';', $fields);
$fileContent .= "\n".implode("\n", $exportDataset);

$fileName = 'export_data_' . date('YmdHis') . '.csv';
header('Content-Disposition: attachment; filename="' . $fileName . '"; charset=utf-8');
rex_response::sendContent($fileContent, 'application/octetstream');

exit;

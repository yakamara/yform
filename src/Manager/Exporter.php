<?php

namespace Yakamara\YForm\Manager;

use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Http\Response;

use function count;
use function in_array;

class Exporter
{
    private ?Query $query = null;
    private $limit_interval = 1000;
    private array $filter = [];
    private array $set = [];
    private ?array $searchObject = null;
    private array $field_names = [];
    private ?string $filename = null;
    private string $delimiter = ';';

    public function __construct(Query $query, array $filter = [], array $set = [], ?array $searchObject = null)
    {
        $this->filter = $filter;
        $this->set = $set;
        $this->searchObject = $searchObject;
        $this->query = $query;
    }

    public function setFieldNames(array $field_names): void
    {
        $this->field_names = $field_names;
    }

    public function setLimitInterval(int $limit_interval): void
    {
        $this->limit_interval = $limit_interval;
    }

    public function getFilename(): ?string
    {
        if (null === $this->filename) {
            $this->filename = 'export_data_' . date('YmdHis') . '.csv';
        }
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function sendExport(): void
    {
        Response::cleanOutputBuffers();

        $ExtensionPortQuery = Extension::dispatch(
            new ExtensionPoint('YFORM_DATA_TABLE_EXPORT', $this->query, [
                'filter' => $this->filter,
                'set' => $this->set,
                'searchObject' => $this->searchObject,
            ]));

        /** @var Query $ExtensionPortQuery */
        $table = $ExtensionPortQuery->getTable();
        $columns = $table->getColumns();

        $fields = ['id' => '"id"'];
        $selectFields = [];
        $selectFields[] = 'id';

        foreach ($table->getFields() as $field) {
            if ('value' == $field->getType() && 'none' != $field->getDatabaseFieldType()) {
                if (0 == count($this->field_names) || in_array($field->getName(), $this->field_names, true)) {
                    $fields[$field->getName()] = '"' . $field->getName() . '"';
                    if (isset($columns[$field->getName()])) {
                        $selectFields[] = $field->getName();
                    }
                }
            }
        }

        $ExtensionPortQuery->select($selectFields);

        header('Content-Disposition: attachment; filename="' . $this->getFilename() . '"; charset=utf-8');
        header('Content-Type: application/octetstream; charset=utf-8');

        Response::cleanOutputBuffers();

        echo pack('CCC', 0xEF, 0xBB, 0xBF);
        echo implode($this->getDelimiter(), $fields);

        $count = 0;

        while (true) {
            $ExtensionPortQuery->limit($count, $this->limit_interval);
            $count += $this->limit_interval;

            $Dataa = $ExtensionPortQuery->find();

            if (0 == count($Dataa)) {
                break;
            }

            foreach ($Dataa as $data) {
                $Line = [];
                foreach ($fields as $fieldName => $fV) {
                    $Line[$fieldName] = '"' . str_replace(['"', "\n", "\r"],
                        ['""', '', ''],
                        @$data->getValue($fieldName) ?? '') . '"';
                }
                echo "\n" . implode($this->getDelimiter(), $Line);
            }
        }

        exit;
    }
}

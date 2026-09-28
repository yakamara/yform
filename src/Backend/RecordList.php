<?php

namespace Yakamara\YForm\Backend;

use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Yakamara\YForm\Data\Paginated;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Table\ListConfig;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\View\ViewRenderer;

use function array_key_exists;
use function count;
use function in_array;

/**
 * Backend list of the records of a table: quick search, sorting and pagination from the request.
 *
 * @internal
 */
final class RecordList
{
    public readonly ListConfig $config;
    public readonly string $search;
    public readonly string $sort;
    public readonly string $direction;
    public readonly int $page;

    /** @var array<string, array{label: string, sortable: bool}> */
    public readonly array $columns;

    public function __construct(
        public readonly Table $table,
        public readonly bool $canEdit,
    ) {
        $this->config = $table->list(new ListConfig());
        $this->columns = $this->resolveColumns();

        $this->search = trim(Request::get('search', 'string', ''));
        $sort = Request::get('sort', 'string', '');
        $sortable = 'id' === $sort || (isset($this->columns[$sort]) && $this->columns[$sort]['sortable']);
        $this->sort = $sortable ? $sort : $this->config->sortBy;
        $direction = strtoupper(Request::get('direction', 'string', ''));
        $this->direction = in_array($direction, ['ASC', 'DESC'], true) ? $direction : ('' === $sort ? $this->config->sortDirection : 'ASC');
        $this->page = max(1, Request::get('p', 'int', 1));
    }

    /** Query with the list filters applied (also used to restrict editing). */
    public function baseQuery(): Query
    {
        $query = $this->table->query();
        foreach ($this->config->filters as $filter) {
            $filter($query);
        }
        return $query;
    }

    /** @return Paginated<Record> */
    public function result(): Paginated
    {
        $query = $this->baseQuery();

        if ('' !== $this->search) {
            $term = $this->search;
            $fields = [];
            foreach ($this->table->storedFields() as $field) {
                if ($field->searchable) {
                    $fields[] = $field;
                }
            }
            $query->whereAny(static function (Query $q) use ($fields, $term): void {
                if (ctype_digit($term)) {
                    $q->where('id', (int) $term);
                }
                foreach ($fields as $field) {
                    $field->applySearch($q, $term);
                }
            });
        }

        $query->orderBy($this->sort, $this->direction);
        if ('id' !== $this->sort) {
            $query->orderBy('id', $this->direction);
        }

        return $query->paginate($this->page, $this->config->perPage);
    }

    public function cell(string $column, Record $record): string
    {
        if (isset($this->config->computed[$column])) {
            return ($this->config->computed[$column]['render'])($record);
        }
        if ('id' === $column) {
            return (string) $record->id;
        }
        $value = $record->get($column);
        if (isset($this->config->formatters[$column])) {
            return ($this->config->formatters[$column])($value, $record);
        }
        return $this->table->getField($column)->listValue($value, $record);
    }

    /** @param array<string, string|int|null> $params `null` removes a parameter */
    public function url(array $params = []): string
    {
        $params = [
            'table' => $this->table->name,
            'search' => '' === $this->search ? null : $this->search,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'p' => $this->page > 1 ? $this->page : null,
            ...$params,
        ];
        return Url::backendPage('yform/data', array_filter($params, static fn ($value): bool => null !== $value));
    }

    public function sortUrl(string $column): string
    {
        $direction = $this->sort === $column && 'ASC' === $this->direction ? 'DESC' : 'ASC';
        return $this->url(['sort' => $column, 'direction' => $direction, 'p' => 1]);
    }

    public function render(): string
    {
        return ViewRenderer::render(self::class, $this, $this->result());
    }

    /** @return array<string, array{label: string, sortable: bool}> */
    private function resolveColumns(): array
    {
        $fields = $this->table->getFields();
        $names = $this->config->columns;
        if (null === $names) {
            $names = ['id'];
            foreach ($fields as $name => $field) {
                if ($field->listed && $field->storesValue()) {
                    $names[] = $name;
                }
            }
        }
        foreach ($this->config->computed as $name => $computed) {
            if (!in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        $columns = [];
        foreach ($names as $name) {
            if ('id' === $name) {
                $columns['id'] = ['label' => 'ID', 'sortable' => true];
            } elseif (array_key_exists($name, $this->config->computed)) {
                $columns[$name] = ['label' => $this->config->computed[$name]['label'], 'sortable' => false];
            } elseif (isset($fields[$name]) && $fields[$name]->storesValue()) {
                $field = $fields[$name];
                $columns[$name] = ['label' => $field->translatedLabel(), 'sortable' => $field->sortable && $this->isSingleColumn($field)];
            }
        }
        return $columns;
    }

    private function isSingleColumn(Field $field): bool
    {
        $columns = $field->columns();
        return 1 === count($columns) && $columns[0]->name === $field->name;
    }
}

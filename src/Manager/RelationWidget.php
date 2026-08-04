<?php

namespace Yakamara\YForm\Manager;

use Redaxo\Core\Form\Select\Select;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Fragment;

use function implode;
use function sprintf;
use function urlencode;

use function Redaxo\Core\View\escape;

/**
 * The backend widgets of the `be_manager_relation` field.
 *
 * In REDAXO 5 these lived on `rex_var_yform_table_data`, a REX_VAR class that doubled as a widget
 * factory. REDAXO 6 removed the REX_VAR template-variable system entirely (`RexVar\` now only holds
 * four widget helpers), so the REX_VAR half — `REX_YFORM_DATASET[...]` output for modules and actions —
 * is gone; in REDAXO 6 a module class queries {@see Dataset} directly instead. The widget half is
 * still needed by the field and lives here.
 */
final class RelationWidget
{
    /**
     * @param array{link: string, size?: int, attributes?: array<string, string>, options: list<array{id: int|string, name: string}>, _csrf_token: string, fieldName: string} $args
     */
    public static function getMultipleWidget(int|string $id, string $name, string $value, array $args = []): string
    {
        $link = $args['link'];
        $size = $args['size'] ?? 10;

        $attributes = ['class' => 'form-control yform-dataset-view'];
        $attributes = array_merge($attributes, $args['attributes'] ?? []);

        $select = new Select();
        $select->setAttributes($attributes);
        $select->setName($name . '-name');
        $select->setSize($size);
        foreach ($args['options'] as $option) {
            $select->addOption($option['name'], $option['id']);
        }

        $viewButton = '<a class="btn btn-popup yform-dataset-widget-view" title="' . I18n::msg('yform_relation_view_entry') . '"><i class="rex-icon rex-icon-view"></i></a>';

        $e = [];
        $e['field'] = $select->get() . '
                <input type="hidden" class="yform-dataset-real" name="' . $name . '" value="' . escape($value) . '" />';

        $e['moveButtons'] = '
                <a class="btn btn-popup yform-dataset-widget-move yform-dataset-widget-move-top" title="' . I18n::msg('yform_relation_move_first_data') . '"><i class="rex-icon rex-icon-top"></i></a>
                <a class="btn btn-popup yform-dataset-widget-move yform-dataset-widget-move-up" title="' . I18n::msg('yform_relation_move_up_data') . '>"><i class="rex-icon rex-icon-up"></i></a>
                <a class="btn btn-popup yform-dataset-widget-move yform-dataset-widget-move-down" title="' . I18n::msg('yform_relation_down_first_data') . '"><i class="rex-icon rex-icon-down"></i></a>
                <a class="btn btn-popup yform-dataset-widget-move yform-dataset-widget-move-bottom" title="' . I18n::msg('yform_relation_move_last_data') . '"><i class="rex-icon rex-icon-bottom"></i></a>';
        $e['functionButtons'] = '
                <a class="btn btn-popup yform-dataset-widget-open" title="' . I18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-view-list"></i></a>
                <a class="btn btn-popup yform-dataset-widget-add" title="' . I18n::msg('yform_relation_add_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a class="btn btn-popup yform-dataset-widget-delete" title="' . I18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>
                ' . $viewButton;
        $e['before'] = '<div class="yform-dataset-widget"
            data-widget_type="multiple"
            data-id="' . $id . '"
            data-link="' . $link . '"
            data-csrf_token="' . urlencode($args['_csrf_token']) . '"
            data-field_name="' . urlencode($args['fieldName']) . '">';
        $e['after'] = '</div>';

        $fragment = new Fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget_list.php');
    }

    /**
     * @param array{link: string, valueName?: string, _csrf_token: string, fieldName: string} $args
     */
    public static function getSingleWidget(int|string $id, string $name, string $value, array $args = []): string
    {
        $link = $args['link'];
        $valueName = '';
        if ('' != $value) {
            $valueName = escape(trim(sprintf('%s [%s]', $args['valueName'] ?? '', $value)));
        }

        $viewButton = '<a class="btn btn-popup yform-dataset-widget-view" title="' . I18n::msg('yform_relation_view_entry') . '"><i class="rex-icon rex-icon-view"></i></a>';

        $e = [];
        $e['field'] = '
            <input class="form-control yform-dataset-view" type="text" value="' . $valueName . '" readonly="readonly" />
            <input type="hidden" class="yform-dataset-real" name="' . $name . '" value="' . $value . '" />';
        $e['functionButtons'] = '
                <a class="btn btn-popup yform-dataset-widget-open" title="' . I18n::msg('yform_relation_choose_entry') . '"><i class="rex-icon rex-icon-view-list"></i></a>
                <a class="btn btn-popup yform-dataset-widget-add" title="' . I18n::msg('yform_relation_add_entry') . '"><i class="rex-icon rex-icon-add"></i></a>
                <a class="btn btn-popup yform-dataset-widget-delete" title="' . I18n::msg('yform_relation_delete_entry') . '"><i class="rex-icon rex-icon-remove"></i></a>
                ' . $viewButton;
        $e['before'] = '<div class="yform-dataset-widget"
            data-widget_type="single"
            data-id="' . $id . '"
            data-value_name="' . $valueName . '"
            data-value="' . $value . '"
            data-link="' . $link . '"
            data-csrf_token="' . urlencode($args['_csrf_token']) . '"
            data-field_name="' . urlencode($args['fieldName']) . '">';
        $e['after'] = '</div>';

        $fragment = new Fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget.php');
    }

    /** @param list<int|string> $value */
    public static function getRelationWidget(int|string $id, string $fieldName, array $value, string $link, int $mainId): string
    {
        $e = [];
        $e['field'] = '<input type="hidden" name="' . $fieldName . '" id="YFORM_DATASET_' . $id . '" value="' . implode(',', $value) . '" />';
        $e['before'] = '<div class="yform-dataset-widget"
            data-widget_type="pool"
            data-link="' . $link . '">';
        $e['after'] = '';
        if ($mainId > 0) {
            $e['functionButtons'] = '<a class="btn btn-popup yform-dataset-widget-pool">' . I18n::msg('yform_relation_edit_relations') . '</a>';
        } else {
            $e['after'] = '<p class="help-block small">' . I18n::msg('yform_relation_first_create_data') . '</p>';
        }
        $e['after'] .= '</div>';

        $fragment = new Fragment();
        $fragment->setVar('elements', [$e], false);
        return $fragment->parse('core/form/widget.php');
    }
}

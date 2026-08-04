<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$second ??= 0;
$minute ??= 0;
$hour ??= 0;
$day ??= 0;
$month ??= 0;
$year ??= 0;
$format ??= 'YYYY-MM-DD HH:ii:ss';
$yearStart ??= '1800';
$yearEnd ??= '2100';

$notices = [];
if ('' != $yfield->getElement('notice')) {
    $notices[] = \Redaxo\Core\Translation\I18n::translate($yfield->getElement('notice'), false);
}
if (isset($yfield->params['warning_messages'][$yfield->getId()]) && !$yfield->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">' . \Redaxo\Core\Translation\I18n::translate($yfield->params['warning_messages'][$yfield->getId()]) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notices) . '</p>';
}

$class_group = trim('form-group ' . $yfield->getWarningClass());
$class_label[] = 'control-label';

$output = $format;

$search = [];
$replace = [];

$pos = strpos($format, 'YYYY');
if (false !== $pos) {
    $attributes = $yfield->getAttributeElements([
        'class' => trim('form-control ' . $yfield->getWarningClass()),
        'id' => $yfield->getFieldId('year'),
        'name' => $yfield->getFieldName() . '[year]',
    ], ['required', 'disabled', 'readonly']);

    $replace_i = '<select ' . implode(' ', $attributes) . '>';
    $replace_i .= '<option value="00">--</option>';
    for ($i = $yearStart; $i <= $yearEnd; ++$i):
        $selected = (@$year == $i) ? ' selected="selected"' : '';
        $replace_i .= '<option value="' . $i . '"' . $selected . '>' . str_pad($i, 4, '0', STR_PAD_LEFT) . '</option>';
    endfor;
    $replace_i .= '</select>';
    $replace['YYYY'] = $replace_i;
    $search[] = 'YYYY';
}

$pos = strpos($format, 'MM');
if (false !== $pos) {
    $attributes = $yfield->getAttributeElements([
        'class' => trim('form-control ' . $yfield->getWarningClass()),
        'id' => $yfield->getFieldId('month'),
        'name' => $yfield->getFieldName() . '[month]',
    ], ['required', 'disabled', 'readonly']);

    $replace_i = '<select ' . implode(' ', $attributes) . '>';
    $replace_i .= '<option value="00">--</option>';
    for ($i = 1; $i < 13; ++$i):
        $selected = (@$month == $i) ? ' selected="selected"' : '';
        $replace_i .= '<option value="' . $i . '"' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
    endfor;
    $replace_i .= '</select>';
    $replace['MM'] = $replace_i;
    $search[] = 'MM';
}

$pos = strpos($format, 'DD');
if (false !== $pos) {
    $attributes = $yfield->getAttributeElements([
        'class' => trim('form-control ' . $yfield->getWarningClass()),
        'id' => $yfield->getFieldId('day'),
        'name' => $yfield->getFieldName() . '[day]',
    ], ['required', 'disabled', 'readonly']);

    $replace_i = '<select ' . implode(' ', $attributes) . '>';
    $replace_i .= '<option value="00">--</option>';
    for ($i = 1; $i < 32; ++$i):
        $selected = (@$day == $i) ? ' selected="selected"' : '';
        $replace_i .= '<option value="' . $i . '"' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
    endfor;
    $replace_i .= '</select>';
    $replace['DD'] = $replace_i;
    $search[] = 'DD';
}

$pos = strpos($format, 'HH');
if (false !== $pos) {
    $attributes = $yfield->getAttributeElements([
        'class' => trim('form-control ' . $yfield->getWarningClass()),
        'id' => $yfield->getFieldId('hour'),
        'name' => $yfield->getFieldName() . '[hour]',
    ], ['required', 'disabled', 'readonly']);

    $replace_i = '<select ' . implode(' ', $attributes) . '>';
    for ($i = 0; $i < 24; ++$i) {
        $selected = (@$hour == $i) ? ' selected="selected"' : '';
        $replace_i .= '<option value="' . $i . '" ' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
    }
    $replace_i .= '</select>';
    $replace['HH'] = $replace_i;
    $search[] = 'HH';
}

$pos = strpos($format, 'ii');
if (false !== $pos) {
    $attributes = $yfield->getAttributeElements([
        'class' => trim('form-control ' . $yfield->getWarningClass()),
        'id' => $yfield->getFieldId('minute'),
        'name' => $yfield->getFieldName() . '[minute]',
    ], ['required', 'disabled', 'readonly']);

    $replace_i = '<select ' . implode(' ', $attributes) . '>';
    for ($i = 0; $i < 60; ++$i) {
        $selected = (@$minute == $i) ? ' selected="selected"' : '';
        $replace_i .= '<option value="' . $i . '" ' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
    }
    $replace_i .= '</select>';
    $replace['ii'] = $replace_i;
    $search[] = 'ii';
}

$pos = strpos($format, 'ss');
if (false !== $pos) {
    $attributes = $yfield->getAttributeElements([
        'class' => trim('form-control ' . $yfield->getWarningClass()),
        'id' => $yfield->getFieldId('second'),
        'name' => $yfield->getFieldName() . '[second]',
    ], ['required', 'disabled', 'readonly']);

    $replace_i = '<select ' . implode(' ', $attributes) . '>';
    for ($i = 0; $i < 60; ++$i) {
        $selected = (@$second == $i) ? ' selected="selected"' : '';
        $replace_i .= '<option value="' . $i . '"' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
    }
    $replace_i .= '</select>';
    $replace['ss'] = $replace_i;
    $search[] = 'ss';
}

// $output = str_replace($search, $replace, $output);
$output = strtr($output, $replace);

echo '
    <div class="' . $class_group . '" id="' . $yfield->getHTMLId() . '">
        <label class="' . implode(' ', $class_label) . '" for="' . $yfield->getFieldId() . '">' . $yfield->getLabel() . '</label>
        <div class="form-inline">' . $output . '</div>' . $notice . '
    </div>
';

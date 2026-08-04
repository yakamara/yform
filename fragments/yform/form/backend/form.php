<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\YForm $yform
 */
extract($this->getVariables());
?>
<div id="<?= $yform->objparams['form_wrap_id'] ?>" class="<?= $yform->objparams['form_wrap_class'] ?>">

    <?php
    if ('' != $yform->objparams['form_action']) {
        $action_url = $yform->objparams['form_action'];
        $action_url_splitted = explode('?', $action_url);

        $query_array = [];
        if (2 == count($action_url_splitted)) {
            parse_str(html_entity_decode($action_url_splitted[1]), $query_array);
        }
        if (0 < count($yform->objparams['form_action_query_params'])) {
            $query_array += $yform->objparams['form_action_query_params'];
            $action_url = $action_url_splitted[0] . '?' . http_build_query($query_array, '', '&amp;', PHP_QUERY_RFC3986);
        }

        echo '<form action="' . $action_url . '" method="' . $yform->objparams['form_method'] . '" id="' . $yform->objparams['form_name'] . '" class="' . $yform->objparams['form_class'] . '" enctype="multipart/form-data">';
    }
    ?>

        <?php
        if (!$yform->objparams['hide_top_warning_messages']) {
            if ($yform->objparams['warning_messages'] || $yform->objparams['unique_error']) {
                echo $yform->parse('errors');
            }
        }
        ?>

        <?php foreach ($yform->objparams['form_output'] as $field):
            echo $field;
        endforeach ?>

        <?php for ($i = 0; $i < $yform->objparams['fieldsets_opened']; ++$i):
            echo $yform->parse('value/fieldset', ['option' => 'close']);
        endfor ?>

        <?php

        $recArray = static function ($key, $paramsArray) use (&$recArray) {
            if (!is_array($paramsArray)) {
                echo "\n" . '<input type="hidden" name="' . $key . '" value="' . \Redaxo\Core\View\escape($paramsArray) . '" />';
            } elseif (is_array($paramsArray)) {
                foreach ($paramsArray as $k => $v) {
                    $recArray($key . '[' . $k . ']', $v);
                }
            }
        };
        foreach ($yform->objparams['form_hiddenfields'] as $k => $v) {
            $recArray($k, $v);
        }

        ?>

    <?php
    if ('' != $yform->objparams['form_action']) {
        echo '</form>';
    }
    ?>

</div>

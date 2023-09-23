<?php

/**
 * @var rex_yform $this
 * @psalm-scope-this rex_yform
 */

?>
<div id="<?= $this->objparams['form_wrap_id'] ?>" class="<?= $this->objparams['form_wrap_class'] ?>">

    <?php
    if ('' != $this->objparams['form_action']) {
        $action_url = $this->objparams['form_action'];
        $action_url_splitted = explode('?', $action_url);

        $query_array = [];
        if (2 == count($action_url_splitted)) {
            parse_str(html_entity_decode($action_url_splitted[1]), $query_array);
        }
        if (0 < count($this->objparams['form_action_query_params'])) {
            $query_array += $this->objparams['form_action_query_params'];
            $action_url = $action_url_splitted[0] . '?' . http_build_query($query_array, '', '&amp;', PHP_QUERY_RFC3986);
        }

        echo '<form action="' . $action_url . '" method="' . $this->objparams['form_method'] . '" id="' . $this->objparams['form_name'] . '" class="' . $this->objparams['form_class'] . '" enctype="multipart/form-data">';
    }
    ?>

        <?php
        if (!$this->objparams['hide_top_warning_messages']) {
            if ($this->objparams['warning_messages'] || $this->objparams['unique_error']) {
                echo $this->parse('errors.tpl.php');
            }
        }
        ?>

        <?php foreach ($this->objparams['form_output'] as $field):
            echo $field;
        endforeach ?>

        <?php for ($i = 0; $i < $this->objparams['fieldsets_opened']; ++$i):
            echo $this->parse('value.fieldset.tpl.php', ['option' => 'close']);
        endfor ?>

        <?php

        $recArray = static function ($key, $paramsArray) use (&$recArray) {
            if (!is_array($paramsArray)) {
                echo "\n" . '<input type="hidden" name="' . $key . '" value="' . rex_escape($paramsArray) . '" />';
            } elseif (is_array($paramsArray)) {
                foreach ($paramsArray as $k => $v) {
                    $recArray($key . '[' . $k . ']', $v);
                }
            }
        };
        foreach ($this->objparams['form_hiddenfields'] as $k => $v) {
            $recArray($k, $v);
        }

        ?>

    <?php
    if ('' != $this->objparams['form_action']) {
        echo '</form>';
    }
    ?>

</div>

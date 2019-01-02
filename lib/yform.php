<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform
{
    public static $TemplatePaths = [];

    private $fieldsInitialized = false;

    public function __construct()
    {
        $this->objparams = [];

        // --------------------------- editable via objparams|key|newvalue

        $this->objparams['answertext'] = '';
        $this->objparams['submit_btn_label'] = 'Abschicken';
        $this->objparams['submit_btn_show'] = true;

        $this->objparams['values'] = [];
        $this->objparams['validates'] = [];
        $this->objparams['actions'] = [];

        $this->objparams['error_class'] = 'has-error';
        $this->objparams['unique_error'] = '';
        $this->objparams['unique_field_warning'] = 'not unique';

        $this->objparams['article_id'] = '';
        $this->objparams['clang'] = '';

        $this->objparams['real_field_names'] = false;

        $this->objparams['form_method'] = 'post';
        $this->objparams['form_action'] = 'index.php';
        $this->objparams['form_anchor'] = '';
        $this->objparams['form_showformafterupdate'] = 0;
        $this->objparams['form_show'] = true;
        $this->objparams['form_name'] = 'formular';
        $this->objparams['form_class'] = 'rex-yform';
        $this->objparams['form_wrap_id'] = 'rex-yform';
        $this->objparams['form_wrap_class'] = 'yform';

        $this->objparams['form_label_type'] = 'html'; // plain

        $this->objparams['form_ytemplate'] = 'bootstrap,classic';

        $this->objparams['actions_executed'] = false;
        $this->objparams['postactions_executed'] = false;
        $this->objparams['preactions_executed'] = false;

        $this->objparams['Error-occured'] = '';
        $this->objparams['Error-Code-EntryNotFound'] = 'ErrorCode - EntryNotFound';
        $this->objparams['Error-Code-InsertQueryError'] = 'ErrorCode - InsertQueryError';

        $this->objparams['csrf_protection'] = true;
        $this->objparams['csrf_protection_error_message'] = '{{ csrf.error }}';

        $this->objparams['getdata'] = false;
        $this->objparams['data'] = false;
        $this->objparams['get_field_type'] = 'request';


        // --------------------------- do not edit

        $this->objparams['debug'] = false;

        $this->objparams['form_data'] = ''; // Forms with pipe notation
        $this->objparams['output'] = ''; // Final output of form

        // predefined dataset via sql
        $this->objparams['main_where'] = ''; // like "id=12" for db
        $this->objparams['main_id'] = -1; // unique Dataset ID
        $this->objparams['main_table'] = ''; // for db and unique
        $this->objparams['sql_object'] = null; // rex_sql

        $this->objparams['form_hiddenfields'] = [];

        $this->objparams['warning'] = [];
        $this->objparams['warning_messages'] = [];

        $this->objparams['hide_top_warning_messages'] = false;
        $this->objparams['hide_field_warning_messages'] = true;

        $this->objparams['fieldsets_opened'] = 0;

        $this->objparams['form_elements'] = [];
        $this->objparams['form_output'] = [];
        $this->objparams['form_needs_output'] = true;

        $this->objparams['value_pool'] = [];
        $this->objparams['value_pool']['email'] = [];
        $this->objparams['value_pool']['sql'] = [];
        $this->objparams['value_pool']['files'] = [];

        $this->objparams['value'] = [];
        $this->objparams['validate'] = [];
        $this->objparams['action'] = [];

        $this->objparams['form_array'] = [];

        $this->objparams['this'] = $this;
    }

    public static function factory()
    {
        return new self();
    }

    public static function addTemplatePath($path)
    {
        self::$TemplatePaths[] = $path;
    }

    public function setDebug($s = true)
    {
        $this->objparams['debug'] = $s;
    }

    public function setFormData($form_definitions, $refresh = true)
    {
        $this->setObjectparams('form_data', $form_definitions, $refresh);

        $this->objparams['form_data'] = str_replace("\n\r", "\n", $this->objparams['form_data']); // Die Definitionen
        $this->objparams['form_data'] = str_replace("\r", "\n", $this->objparams['form_data']); // Die Definitionen

        if (!is_array($this->objparams['form_elements'])) {
            $this->objparams['form_elements'] = [];
        }

        $form_elements_tmp = explode("\n", $this->objparams['form_data']);

        // CLEAR EMPTY AND COMMENT LINES
        foreach ($form_elements_tmp as $form_element) {
            $form_element = trim($form_element);
            if ($form_element != '' && $form_element[0] != '#' && $form_element[0] != '/') {
                $this->objparams['form_elements'][] = explode('|', trim($form_element));
            }
        }
    }

    public function setValueField($type = '', $values = [])
    {
        $values = array_merge([$type], $values);
        $this->objparams['form_elements'][] = $values;
    }

    public function setValidateField($type = '', $values = [])
    {
        $values = array_merge(['validate', $type], $values);
        $this->objparams['form_elements'][] = $values;
    }

    public function setActionField($type = '', $values = [])
    {
        $values = array_merge(['action', $type], $values);
        $this->objparams['form_elements'][] = $values;
    }

    public function setRedaxoVars($aid = '', $clang = '', $params = [])
    {
        if ($clang == '') {
            $clang = rex_clang::getCurrentId();
        }
        if ($aid == '') {
            $aid = rex_article::getCurrentId();
        }

        $this->setObjectparams('form_action', rex_getUrl($aid, $clang, $params));
    }

    public function setHiddenField($k, $v)
    {
        $this->objparams['form_hiddenfields'][$k] = $v;
    }

    public function setObjectparams($k, $v, $refresh = true)
    {
        if (!$refresh && isset($this->objparams[$k])) {
            $this->objparams[$k] .= $v;
        } else {
            $this->objparams[$k] = $v;
        }
        return $this->objparams[$k];
    }

    public function getObjectparams($key)
    {
        if (!isset($this->objparams[$key])) {
            return false;
        }
        return $this->objparams[$key];
    }

    public function getForm()
    {
        rex_extension::registerPoint(new rex_extension_point('YFORM_GENERATE', $this));
        $this->executeFields();
        return $this->executeActions();
    }

    public function executeFields()
    {
        if (!$this->fieldsInitialized) {
            $this->initializeFields();
        }

        foreach ($this->objparams['values'] as $ValueObject) {
            $ValueObject->setValue($this->getFieldValue($ValueObject->getName(), [$ValueObject->getId()]));
        }

        // *************************************************** OBJECT PARAM "send"
        if ($this->getFieldValue('send') == '1') {
            $this->objparams['send'] = 1;
        }

        // *************************************************** PRE VALUES
        // Felder aus Datenbank auslesen - Sofern Aktualisierung
        if ($this->objparams['getdata']) {
            if (!$this->objparams['sql_object'] instanceof rex_sql) {
                $this->objparams['sql_object'] = rex_sql::factory();
                $this->objparams['sql_object']->setDebug($this->objparams['debug']);
                $this->objparams['sql_object']->setQuery('SELECT * from ' . $this->objparams['main_table'] . ' WHERE ' . $this->objparams['main_where']);
            }
            if ($this->objparams['sql_object']->getRows() > 1 || $this->objparams['sql_object']->getRows() == 0) {
                $this->objparams['warning'][] = $this->objparams['Error-Code-EntryNotFound'];
                $this->objparams['warning_messages'][] = $this->objparams['Error-Code-EntryNotFound'];
                $this->objparams['form_show'] = true;
                unset($this->objparams['sql_object']);
            }
        }

        // ----- Felder mit Werten fuellen, fuer wiederanzeige
        // Die Value Objekte werden mit den Werten befuellt die
        // aus dem Formular nach dem Abschicken kommen
        if ($this->objparams['send'] != 1 && $this->objparams['main_where'] != '') {
            foreach ($this->objparams['values'] as $i => $valueObject) {
                if ($valueObject->getName()) {
                    if (isset($this->objparams['sql_object'])) {
                        $this->setFieldValue($valueObject->getName(), [$i], @$this->objparams['sql_object']->getValue($valueObject->getName()));
                    }
                }
                $valueObject->setValue($this->getFieldValue($valueObject->getName(), [$i]));
            }
        }


        // FORM DATA individuell einspielen
        if (isset($this->objparams['data']) && is_array($this->objparams['data']) && count($this->objparams['data'])>0)  {
            foreach ($this->objparams['values'] as $i => $valueObject) {
                if (isset($this->objparams['data'][$valueObject->getName()])) {
                    $valueObject->setValue($this->objparams['data'][$valueObject->getName()]);
                }
            }
        }

        // *************************************************** VALIDATE OBJEKTE

        foreach ($this->objparams['fields'] as $types) {
            foreach ($types as $Object) {
                $Object->preValidateAction();
            }
        }

        if ($this->objparams['send'] == 1) {
            foreach ($this->objparams['validates'] as $Object) {
                $Object->enterObject();
            }
        }

        foreach ($this->objparams['fields'] as $types) {
            foreach ($types as $Object) {
                $Object->postValidateAction();
            }
        }

        // *************************************************** FORMULAR ERSTELLEN

        foreach ($this->objparams['values'] as $ValueObject) {
            $ValueObject->enterObject();
        }

        if ($this->objparams['send'] == 1) {
            foreach ($this->objparams['validates'] as $Object) {
                $Object->postValueAction();
            }
        }

        // ***** PostFormActions
        foreach ($this->objparams['values'] as $ValueObject) {
            $ValueObject->postFormAction();
        }
    }

    public function initializeFields()
    {
        $this->objparams['values'] = [];
        $this->objparams['validates'] = [];
        $this->objparams['actions'] = [];

        $this->objparams['fields'] = [];

        $this->objparams['fields']['values'] = &$this->objparams['values'];
        $this->objparams['fields']['validates'] = &$this->objparams['validates'];
        $this->objparams['fields']['actions'] = &$this->objparams['actions'];

        $this->objparams['send'] = 0;

        // *************************************************** VALUE OBJECT INIT

        $this->setCSRFField();

        $rows = count($this->objparams['form_elements']);

        for ($i = 0; $i < $rows; ++$i) {
            $element = $this->objparams['form_elements'][$i];

            if ($element[0] == 'validate') {
                $class = 'rex_yform_validate_' . trim($element[1]);
                $type = 'validates';
            } else if ($element[0] == 'action') {
                $class = 'rex_yform_action_' . trim($element[1]);
                $type = 'actions';
            } else {
                $class = 'rex_yform_value_' . trim($element[0]);
                $type = 'values';
            }

            if (class_exists($class)) {

                $this->objparams[$type][$i] = new $class();
                $this->objparams[$type][$i]->loadParams($this->objparams, $element);
                $this->objparams[$type][$i]->setId($i);
                $this->objparams[$type][$i]->init();
                $this->objparams[$type][$i]->setObjects($this->objparams['values']);
                $rows = count($this->objparams['form_elements']); // if elements have changed -> new rowcount

                // special case - submit button shows up by default
                if (($rows - 1) == $i && $this->objparams['submit_btn_show']) {
                    ++$rows;
                    $this->objparams['form_elements'][] = ['submit', 'name' => 'rex_yform_submit', 'label' => $this->objparams['submit_btn_label'], 'no_db' => 'no_db'];
                    $this->objparams['submit_btn_show'] = false;
                }
            } else {
                echo 'Class does not exist "' . $class . '" ';
            }
        }

        $this->fieldsInitialized = true;
    }

    public function executeActions()
    {
        // *************************************************** ACTION OBJEKTE

        // ID setzen, falls vorhanden
        if ($this->objparams['main_id'] > 0) {
            $this->objparams['value_pool']['email']['ID'] = $this->objparams['main_id'];
        }

        $hasWarnings = count($this->objparams['warning']) != 0;
        $hasWarningMessages = count($this->objparams['warning_messages']) != 0;

        // ----- Actions
        if ($this->objparams['send'] == 1 && !$hasWarnings && !$hasWarningMessages) {
            $this->objparams['form_show'] = false;

            // ----- pre Actions
            foreach ($this->objparams['fields'] as $t => $types) {
                foreach ($types as $Objects) {
                    if (!is_array($Objects)) {
                        $Objects = [$Objects];
                    }
                    foreach ($Objects as $Object) {
                        $Object->preAction();
                    }
                }
            }
            $this->objparams['preactions_executed'] = true;

            // ----- normal Actions
            foreach ($this->objparams['fields'] as $t => $types) {
                foreach ($types as $Objects) {
                    if (!is_array($Objects)) {
                        $Objects = [$Objects];
                    }
                    foreach ($Objects as $Object) {
                        $Object->executeAction();
                    }
                }
            }
            $this->objparams['actions_executed'] = true;

            // ----- post Actions
            foreach ($this->objparams['fields'] as $types) {
                foreach ($types as $Objects) {
                    if (!is_array($Objects)) {
                        $Objects = [$Objects];
                    }
                    foreach ($Objects as $Object) {
                        $Object->postAction();
                    }
                }
            }
            $this->objparams['postactions_executed'] = true;
        }

        if ($this->objparams['form_showformafterupdate']) {
            $this->objparams['form_show'] = true;
        }

        if ($this->objparams['form_show']) {
            // -------------------- send definition
            $this->setHiddenField($this->getFieldName('send'), 1);

            // -------------------- form start
            if ($this->objparams['form_anchor'] != '') {
                $this->objparams['form_action'] .= '#' . $this->objparams['form_anchor'];
            }

            // -------------------- formOut
            $this->objparams['output'] .= $this->parse('form.tpl.php');
        }

        return $this->objparams['output'];
    }

    public function getTemplatePath($template)
    {
        $templates = (array) $template;
        foreach (explode(',', $this->objparams['form_ytemplate']) as $form_ytemplate) {
            $ytemplates[$form_ytemplate] = true;
        }

        $ytemplates['default'] = true;
        foreach ($templates as $template) {
            foreach ($ytemplates as $ytemplate => $_) {
                foreach (array_reverse(self::$TemplatePaths) as $path) {
                    $template_path = $path . '/' . $ytemplate . '/' . $template;
                    if (file_exists($template_path)) {
                        return $template_path;
                    }
                }
            }
        }

        trigger_error(sprintf('yform template %s not found', $template), E_USER_WARNING);
    }

    public function parse($template, array $params = [])
    {
        extract($params);
        ob_start();
        include $this->getTemplatePath($template);
        return ob_get_clean();
    }

    public static function getTypes()
    {
        return ['value', 'validate', 'action'];
    }

    public function getFieldName(string $label, array $params = []) // $id = '', $k = '', $label = ''
    {

        if ($this->objparams['real_field_names']) {
            $label = $this->prepareLabel($label);
            return $label ?? current($params);
        }

        if (count($params) == 0) {
            $params = [$label];
        }

        $fieldName = 'FORM['.$this->objparams['form_name'].']';

        $params = array_merge($this->objparams['form_array'], $params);

        foreach($params as $param) {
            $param = $this->prepareLabel($param);
            if ($param != "") {
                $fieldName .= '['.$this->prepareLabel($param).']';
            }
        }

        return $fieldName;

    }

    public function getFieldValue($label = '', array $params = [], $debug = false)
    {
        if (count($params) == 0 && $label != '') {
            $params = [$label];
        }

        $params = array_merge($this->objparams['form_array'], $params);

        $value = null;
        switch($this->getObjectparams('get_field_type')) {
            case 'request':
                if ($this->objparams['real_field_names']) {
                    $value = isset($_REQUEST) ? $_REQUEST : null;
                    $params = [$label];
                    // return ($value[$this->prepareLabel($label)]) ?? '';
                }
                if (!$value) {
                    $value = isset($_REQUEST['FORM'][$this->objparams['form_name']]) ? $_REQUEST['FORM'][$this->objparams['form_name']] : null;
                }
                break;

            default:
                if (isset($this->objparams['field_values'][$this->objparams['form_name']])) {
                    $value = $this->objparams['field_values'][$this->objparams['form_name']];
                }
                break;
        }

        if (in_array("", $params, true)) {
            return '';
        }

        foreach($params as $counter => $param) {
            $param = $this->prepareLabel($param);

            if (is_array($value) && array_key_exists($param, $value)) {
                $value = $value[$param];
            } else {
                $value = null;
                break;
            }
        }

        return (is_null($value)) ? '' : $value;

    }

    public function setFieldValue(string $label, array $params, $value)
    {

        if (count($params) == 0) {
            $params = [$label];
        }

        $params = array_merge($this->objparams['form_array'], $params);

        switch($this->getObjectparams('get_field_type')) {
            case 'request':
                if ($this->objparams['real_field_names']) {
                    $fieldValue = &$_REQUEST;
                } else {
                    if (!isset($_REQUEST['FORM'][$this->objparams['form_name']])) {
                        $_REQUEST['FORM'][$this->objparams['form_name']] = '';
                    }
                    $fieldValue = &$_REQUEST['FORM'][$this->objparams['form_name']];
                }
                break;

            default:
                if (!isset($this->objparams['field_values'][$this->objparams['form_name']])) {
                    $this->objparams['field_values'][$this->objparams['form_name']] = '';
                }
                $fieldValue = &$this->objparams['field_values'][$this->objparams['form_name']];
                break;
        }

        foreach($params as $param) {
            $param = $this->prepareLabel($param);
            if ($param != "") {

                if (!isset($fieldValue[$param]) && !is_array($fieldValue)) {
                    $fieldValue = [];
                } else if (!is_array($fieldValue)) {
                    $fieldValue = [];
                }
                $fieldValue[$param] = $value;
                $fieldValue = &$fieldValue[$param];
            }
        }

        return $value;
    }

    public function prepareLabel($label)
    {
        return preg_replace('/[^a-zA-Z\-\_0-9]/', '-', $label);
    }

    public static function unhtmlentities($text)
    {
        return html_entity_decode($text);
    }

    public static function showHelp($script = false)
    {
        $arr = [
            'value' => 'rex_yform_value_',
            'validate' => 'rex_yform_validate_',
            'action' => 'rex_yform_action_',
        ];

        $classes = rex_autoload::getClasses();
        natsort($classes);
        $classesDescription = [];
        $classesFamousDescription = [];
        $classesDeprecatedDescription = [];
        foreach ($arr as $arr_key => $arr_split) {
            $classesDescription[$arr_key] = '';
            $classesFamousDescription[$arr_key] = '';
            $classesDeprecatedDescription[$arr_key] = '';
            foreach ($classes as $class) {
                $exploded = explode($arr_split, $class);
                if (count($exploded) == 2) {
                    $name = $exploded[1];
                    if ($name != 'abstract') {
                        $class = new $class();
                        $desc = trim($class->getDescription());
                        $definitions = $class->getDefinitions();
                        $definition_desc = isset($definitions['description']) ? $definitions['description'] : '';
                        if ($desc != '') {
                            $desc = '<code>' . $desc . '</code>';
                        }
                        if ($definition_desc != '') {
                            $desc = $definition_desc . '<br />' . $desc;
                        }

                        if (isset($definitions['formbuilder']) && !$definitions['formbuilder']) {

                        } elseif ($class->isDeprecated()) {

                            $deprecatedInfo = isset($definitions['deprecated']) ? $definitions['deprecated'].'<br />' : '';
                            $classesDeprecatedDescription[$arr_key] .= '<tr class="yform-classes-deprecated"><th data-title="' . ucfirst($arr_key) . '"><span class="btn btn-default btn-block"><code>' . $name . '</code></span></th><td class="vertical-middle">' . $deprecatedInfo . $desc . '</td></tr>';

                        } elseif (isset($definitions['famous']) && $definitions['famous']) {
                            $classesFamousDescription[$arr_key] .= '<tr class="yform-classes-famous"><th data-title="' . ucfirst($arr_key) . '"><span class="btn btn-default btn-block"><code>' . $name . '</code></span></th><td class="vertical-middle">' . $desc . '</td></tr>';

                        } else {
                            $classesDescription[$arr_key] .= '<tr><th data-title="' . ucfirst($arr_key) . '"><span class="btn btn-default btn-block"><code>' . $name . '</code></span></th><td class="vertical-middle">' . $desc . '</td></tr>';

                        }
                    }
                }
            }

            $classesDescription[$arr_key] = $classesFamousDescription[$arr_key] . $classesDescription[$arr_key] . $classesDeprecatedDescription[$arr_key];

        }

        $return = '';
        foreach ($classesDescription as $title => $content) {
            $fragment = new rex_fragment();
            $fragment->setVar('title', rex_i18n::msg('yform_' . $title));
            $fragment->setVar('content', '<table class="table table-hover yform-table-help">' . $content . '</table>', false);
            $fragment->setVar('collapse', true);
            $fragment->setVar('collapsed', true);
            $content = $fragment->parse('core/page/section.php');
            $return .= $content;
        }
        return $return;
    }

    public static function getTypeArray()
    {
        $return = [];

        $arr = [
            'value' => 'rex_yform_value_',
            'validate' => 'rex_yform_validate_',
            'action' => 'rex_yform_action_',
        ];

        foreach ($arr as $arr_key => $arr_split) {
            foreach (rex_autoload::getClasses() as $class) {
                $exploded = explode($arr_split, $class);
                if (count($exploded) == 2) {
                    $name = $exploded[1];
                    if ($name != 'abstract') {
                        $class = new $class();
                        $d = $class->getDefinitions();
                        if (count($d) > 0) {
                            $return[$arr_key][$d['name']] = $d;
                        }
                    }
                }
            }
        }

        return $return;
    }

    private function setCSRFField()
    {
        $this->objparams['form_elements'][] = ['csrf', 'name' => '_csrf_token'];
    }
}

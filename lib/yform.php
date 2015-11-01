<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform
{

    static $TemplatePaths = [];


    function rex_yform()
    {


        $this->objparams = array();

        // --------------------------- editable via objparams|key|newvalue

        $this->objparams['answertext'] = '';
        $this->objparams['submit_btn_label'] = 'Abschicken';
        $this->objparams['submit_btn_show'] = true;

        $this->objparams['values'] = array();
        $this->objparams['validates'] = array();
        $this->objparams['actions'] = array();

        $this->objparams['error_class'] = 'form_warning';
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
        $this->objparams['form_id'] = 'form_formular';
        $this->objparams['form_class'] = 'rex-yform';
        $this->objparams['form_wrap_id'] = 'rex-yform';
        $this->objparams['form_wrap_class'] = 'yform';

        $this->objparams['form_label_type'] = 'html'; // plain

        $this->objparams['form_skin'] = 'bootstrap,classic';

        $this->objparams['actions_executed'] = false;
        $this->objparams['postactions_executed'] = false;
        $this->objparams['preactions_executed'] = false;

        $this->objparams['Error-occured'] = '';
        $this->objparams['Error-Code-EntryNotFound'] = 'ErrorCode - EntryNotFound';
        $this->objparams['Error-Code-InsertQueryError'] = 'ErrorCode - InsertQueryError';

        $this->objparams['getdata'] = false;


        // --------------------------- do not edit

        $this->objparams['debug'] = false;

        $this->objparams['form_data'] = '';
        $this->objparams['output'] = '';

        $this->objparams['main_where'] = ''; // z.B. id=12
        $this->objparams['main_id'] = -1; // unique ID
        $this->objparams['main_table'] = ''; // for db and unique
        $this->objparams['sql_object'] = null;

        $this->objparams['form_hiddenfields'] = array();

        $this->objparams['warning'] = array();
        $this->objparams['warning_messages'] = array();

        $this->objparams['hide_top_warning_messages'] = false;
        $this->objparams['hide_field_warning_messages'] = true;

        $this->objparams['fieldsets_opened'] = 0; //

        $this->objparams['form_elements'] = array();
        $this->objparams['form_output'] = array();

        $this->objparams['value_pool'] = array();
        $this->objparams['value_pool']['email'] = array();
        $this->objparams['value_pool']['sql'] = array();

        $this->objparams['value'] = array(); // reserver for classes - $this->objparams["value"]["text"] ...
        $this->objparams['validate'] = array(); // reserver for classes
        $this->objparams['action'] = array(); // reserver for classes

        $this->objparams['this'] = $this;

    }

    static public function factory()
    {
        return new self;

    }

    static function addTemplatePath($path)
    {
        self::$TemplatePaths[] = $path;

    }

    function setDebug($s = true)
    {
        $this->objparams['debug'] = $s;
    }

    function setFormData($form_definitions, $refresh = true)
    {
        $this->setObjectparams('form_data', $form_definitions, $refresh);

        $this->objparams['form_data'] = str_replace("\n\r", "\n" , $this->objparams['form_data']); // Die Definitionen
        $this->objparams['form_data'] = str_replace("\r", "\n" , $this->objparams['form_data']); // Die Definitionen

        if (!is_array($this->objparams['form_elements'])) {
            $this->objparams['form_elements'] = array();
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

    function setValueField($type = '', $values = array())
    {
        $values = array_merge(array($type), $values);
        $this->objparams['form_elements'][] = $values;
    }

    function setValidateField($type = '', $values = array())
    {
        $values = array_merge(array('validate', $type), $values);
        $this->objparams['form_elements'][] = $values;
    }

    function setActionField($type = '', $values = array())
    {
        $values = array_merge(array('action', $type), $values);
        $this->objparams['form_elements'][] = $values;
    }

    function setRedaxoVars($aid = '', $clang = '', $params = array())
    {


        if ($clang == '') {
            $clang = $REX['CUR_CLANG'];
        }
        if ($aid == '') {
            $aid = $REX['ARTICLE_ID'];
        }

        $this->setObjectparams('form_action', rex_getUrl($aid, $clang, $params));
    }

    function setHiddenField($k, $v)
    {
        $this->objparams['form_hiddenfields'][$k] = $v;
    }

    function setObjectparams($k, $v, $refresh = true)
    {
        if (!$refresh && isset($this->objparams[$k])) {
            $this->objparams[$k] .= $v;
        } else {
            $this->objparams[$k] = $v;
        }
        return $this->objparams[$k];
    }

    function getObjectparams($k)
    {
        if (!isset($this->objparams[$k])) {
            return false;
        }
        return $this->objparams[$k];
    }

    function getForm()
    {
        $this->executeFields();
        return $this->executeActions();

    }

    function executeFields()
    {

        $this->objparams['values'] = array();
        $this->objparams['validates'] = array();
        $this->objparams['actions'] = array();

        $this->objparams['fields'] = array();

        $this->objparams['fields']['values'] = &$this->objparams['values'];
        $this->objparams['fields']['validates'] = &$this->objparams['validates'];
        $this->objparams['fields']['actions'] = &$this->objparams['actions'];

        $this->objparams['send'] = 0;

        // *************************************************** VALUE OBJECT INIT

        $rows = count($this->objparams['form_elements']);

        for ($i = 0; $i < $rows; $i++) {

                $element = $this->objparams['form_elements'][$i];

                if ($element[0] == 'validate') {
                        $class = 'rex_yform_validate_' . trim($element[1]);
                        $ValidateObject = new $class;
                        $ValidateObject->loadParams($this->objparams, $element);
                        $ValidateObject->setObjects($this->objparams['values']);
                        $this->objparams['validates'][$element[1]][] = $ValidateObject;
                } else if ($element[0] == 'action') {
                    $class = 'rex_yform_action_' . trim($element[1]);
                    $this->objparams['actions'][$i] = new $class;
                    $this->objparams['actions'][$i]->loadParams($this->objparams, $element);
                    $this->objparams['actions'][$i]->setObjects($this->objparams['values']);

                } else {
                    $class = 'rex_yform_value_' . trim($element[0]);
                    $this->objparams['values'][$i] = new $class;
                    $this->objparams['values'][$i]->loadParams($this->objparams, $element);
                    $this->objparams['values'][$i]->setId($i);
                    $this->objparams['values'][$i]->init();
                    $this->objparams['values'][$i]->setObjects($this->objparams['values']);
                    $rows = count($this->objparams['form_elements']); // if elements have changed -> new rowcount
                }

                // special case - submit button shows up by default
                if (($rows - 1) == $i && $this->objparams['submit_btn_show']) {
                    $rows++;
                    $this->objparams['form_elements'][] = array('submit', 'rex_yform_submit', $this->objparams['submit_btn_label'], 'no_db');
                    $this->objparams['submit_btn_show'] = false;
                }

        }

        foreach ($this->objparams['values'] as $ValueObject) {
            $ValueObject->setValue($this->getFieldValue($ValueObject->getId(), '', $ValueObject->getName()));

        }

        // *************************************************** OBJECT PARAM "send"
        if ($this->getFieldValue('send', '', 'send') == '1') {
            $this->objparams['send'] = 1;
        }

        // *************************************************** PRE VALUES
        // Felder aus Datenbank auslesen - Sofern Aktualisierung
        if ($this->objparams['getdata']) {
            if (!$this->objparams['sql_object'] instanceof rex_sql) {
                $this->objparams['sql_object'] = rex_sql::factory();
                $this->objparams['sql_object']->debugsql = $this->objparams['debug'];
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
                        $this->setFieldValue($i, @addslashes($this->objparams['sql_object']->getValue($valueObject->getName())), '', $valueObject->getName());
                    }
                }
                $valueObject->setValue($this->getFieldValue($i, '', $valueObject->getName()));
            }
        }


        // *************************************************** VALIDATE OBJEKTE

        // ***** PreValidateActions
        foreach ($this->objparams['fields'] as $t => $types){
            foreach ($types as $Objects) {
                if(!is_array($Objects))
                    $Objects = array($Objects);
                foreach($Objects as $Object) {
                    $Object->preValidateAction();
                }
            }
        }


        // ***** Validieren
        if ($this->objparams['send'] == 1) {
            foreach ($this->objparams['validates'] as $ValidateType) {
                foreach ($ValidateType as $ValidateObject) {
                    $ValidateObject->enterObject();
                }
            }
        }

        // ***** PostValidateActions
        foreach ($this->objparams['fields'] as $t => $types){
            foreach ($types as $Objects) {
                if(!is_array($Objects))
                    $Objects = array($Objects);
                foreach($Objects as $Object) {
                    $Object->postValidateAction();
                }
            }
        }

        // *************************************************** FORMULAR ERSTELLEN

        foreach ($this->objparams['values'] as $ValueObject) {
            $ValueObject->enterObject();
        }

        if ($this->objparams['send'] == 1) {
            foreach ($this->objparams['validates'] as $ValidateType) {
                foreach ($ValidateType as $ValidateObject) {
                    $ValidateObject->postValueAction();
                }
            }
        }

        // ***** PostFormActions
        foreach ($this->objparams['values'] as $ValueObject) {
            $ValueObject->postFormAction();
        }


    }


    function executeActions()
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
            foreach ($this->objparams['fields'] as $t => $types){
                foreach ($types as $Objects) {
                    if(!is_array($Objects))
                        $Objects = array($Objects);
                    foreach($Objects as $Object) {
                        $Object->preAction();
                    }
                }
            }
            $this->objparams['preactions_executed'] = true;


            // ----- normal Actions
            foreach ($this->objparams['fields'] as $t => $types){
                foreach ($types as $Objects) {
                    if(!is_array($Objects))
                        $Objects = array($Objects);
                    foreach($Objects as $Object) {
                        $Object->executeAction();
                    }
                }
            }
            $this->objparams['actions_executed'] = true;

            // ----- post Actions
            foreach ($this->objparams['fields'] as $types){
                foreach ($types as $Objects) {
                    if(!is_array($Objects))
                        $Objects = array($Objects);
                    foreach($Objects as $Object) {
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
            $this->setHiddenField($this->getFieldName('send', '', 'send'), 1);

            // -------------------- form start
            if ($this->objparams['form_anchor'] != '') {
                $this->objparams['form_action'] .= '#' . $this->objparams['form_anchor'];
            }

            // -------------------- formOut
            $this->objparams['output'] .= $this->parse('form.tpl.php');

        }

        return $this->objparams['output'];

    }

    function getTemplatePath($template)
    {
        $templates = (array) $template;
        foreach (explode(',', $this->objparams['form_skin']) as $form_skin) {
            $skins[$form_skin] = true;
        }

        $skins['default'] = true;
        foreach ($templates as $template) {
            foreach ($skins as $skin => $_) {
                foreach (array_reverse(self::$TemplatePaths) as $path) {
                    $template_path = $path . '/' . $skin . '/' . $template;
                    if (file_exists($template_path)) {
                        return $template_path;
                    }
                }
            }
        }

        trigger_error(sprintf('yform template %s not found', $template), E_USER_WARNING);
    }

    function parse($template, array $params = array())
    {
        extract($params);
        ob_start();
        include $this->getTemplatePath($template);
        return ob_get_clean();
    }

    static function getTypes()
    {
        return array('value', 'validate', 'action');
    }

    function getFieldName($id = '', $k = '', $label = '')
    {
        $label = $this->prepareLabel($label);
        $k = $this->prepareLabel($k);
        if ($this->objparams['real_field_names'] && $label != '') {
            if ($k == '') {
                return $label;
            } else {
                return $label . '[' . $k . ']';
            }
        } else {
            if ($k == '') {
                return 'FORM[' . $this->objparams['form_name'] . '][' . $id . ']';
            } else {
                return 'FORM[' . $this->objparams['form_name'] . '][' . $id . '][' . $k . ']';
            }
        }
    }

    function getFieldValue($id = '', $k = '', $label = '')
    {
        $label = $this->prepareLabel($label);
        $k = $this->prepareLabel($k);
        if ($this->objparams['real_field_names'] && $label != '') {
            if ($k == '' && isset($_REQUEST[$label])) {
                return $_REQUEST[$label];
            } elseif (isset($_REQUEST[$label][$k])) {
                return $_REQUEST[$label][$k];
            }
        } else {
            if ($k == '' && isset($_REQUEST['FORM'][$this->objparams['form_name']][$id])) {
                return $_REQUEST['FORM'][$this->objparams['form_name']][$id];
            } elseif (isset($_REQUEST['FORM'][$this->objparams['form_name']][$id][$k])) {
                return $_REQUEST['FORM'][$this->objparams['form_name']][$id][$k];
            }
        }
    return '';
    }

    function setFieldValue($id = '', $value = '', $k = '', $label = '')
    {
        $label = $this->prepareLabel($label);
        $k = $this->prepareLabel($k);
        if ($this->objparams['real_field_names'] && $label != '') {
            if ($k == '') {
                $_REQUEST[$label] = $value;
            } else {
                $_REQUEST[$label][$k] = $value;
            }
            return;
        } else {
            if ($k == '') {
                $_REQUEST['FORM'][$this->objparams['form_name']][$id] = $value;
            } else {
                $_REQUEST['FORM'][$this->objparams['form_name']][$id][$k] = $value;
            }
        }
    }

    function prepareLabel($label)
    {
        return preg_replace('/[^a-zA-Z\-\_0-9]/', '-', $label);;
    }

    static function unhtmlentities($text)
    {
        return html_entity_decode($text);
    }

    static function showHelp($script = false)
    {

        $return = '';

        $arr = [
            'value' => 'rex_yform_value_',
            'validate' => 'rex_yform_validate_',
            'action' => 'rex_yform_action_'
        ];

        foreach($arr as $arr_key => $arr_split) {

            $return .= '<li class="type value"><strong class="toggler">'.rex_i18n::msg($arr_key).'</strong>';

            foreach (rex_autoload::getClasses() as $class) {
                $exploded = explode($arr_split, $class);
                if (count($exploded) == 2) {
                    $name = $exploded[1];
                    $return .= '<ul class="yform type '.$arr_key.'">';
                    if ($name != "abstract") {
                        $class = new $class;
                        // $d = $class->getDefinitions();
                        $desc = $class->getDescription();
                        $return .= '<li>'.$desc.'</li>';
                    }
                    $return .= '</ul>';
                }
            }

            $return .= '</li>';

        }

        if ($script) {
            $return .= '
<script type="text/javascript">
(function($){

    $("ul.yform strong.toggler").click(function(){
        var me = $(this);
        var target = $(this).next("ul.yform");
        target.toggle(0, function(){
            if(target.css("display") == "block"){
                me.addClass("opened");
            }else{
                me.removeClass("opened");
            }
        });

    });

})(jQuery)
</script>
';
        }

        return '<ul class="yform root">'.$return.'</ul>';

    }


    static function getTypeArray()
    {

        $return = [];

        $arr = [
            'value' => 'rex_yform_value_',
            'validate' => 'rex_yform_validate_',
            'action' => 'rex_yform_action_'
        ];

        foreach($arr as $arr_key => $arr_split) {

            foreach (rex_autoload::getClasses() as $class) {
                $exploded = explode($arr_split, $class);
                if (count($exploded) == 2) {
                    $name = $exploded[1];
                    if ($name != "abstract") {
                        $class = new $class;
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



}

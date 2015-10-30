<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_radio
{

    var $attributes;
    var $options;
    var $option_selected;

    public function rex_radio()
    {
        $this->init();
    }

    public function init()
    {
        $this->attributes = array();
        $this->resetSelected();
        $this->setName('standard');
        $this->setDisabled(false);
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function delAttribute($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->attributes[$name]);
            return true;
        }
        return false;
    }

    public function hasAttribute($name)
    {
        return isset($this->attributes[$name]);
    }

    public function getAttribute($name, $default = '')
    {
        if ($this->hasAttribute($name)) {
            return $this->attributes[$name];
        }
        return $default;
    }

    public function setDisabled($disabled = true)
    {
        if ($disabled) {
            $this->setAttribute('disabled', 'disabled');
        } else {
            $this->delAttribute('disabled');
        }
    }

    public function setName($name)
    {
        $this->setAttribute('name', $name);
    }

    public function setId($id)
    {
        $this->setAttribute('id', $id);
    }

    /**
     * select style
     * Es ist moeglich sowohl eine Styleklasse als auch einen Style zu uebergeben.
     *
     * Aufrufbeispiel:
     * $sel_media->setStyle('class="inp100"');
     * und/oder
     * $sel_media->setStyle("width:150px;");
     */
    public function setStyle($style)
    {
        if (strpos($style, 'class=') !== false) {
            if (preg_match('/class=["\']?([^"\']*)["\']?/i', $style, $matches)) {
                $this->setAttribute('class', $matches[1]);
            }
        } else {
            $this->setAttribute('style', $style);
        }
    }

    public function setSize($size)
    {
        $this->setAttribute('size', $size);
    }

    public function setSelected($selected)
    {
        $this->option_selected = htmlspecialchars($selected);
    }

    public function resetSelected()
    {
        $this->option_selected = '';
    }

    public function addOption($name, $value, $attributes = array())
    {
        $this->options[] = array('name' => $name, 'value' => $value, 'attributes' => $attributes);
    }

    public function get()
    {
        $attr = '';
        foreach ($this->attributes as $name => $value) {
            $attr .= ' ' . $name . '="' . $value . '"';
        }

        $ausgabe = "\n";
        $ausgabe .= '<div class="radios">';

        if (is_array($this->options)) {
            $ausgabe .= $this->_outOptions();
        }

        $ausgabe .= '</div>' . "\n";

        return $ausgabe;
    }

    public function show()
    {
        echo $this->get();
    }

    private function _outOptions()
    {
        $return = '';

        $selected = '';
        foreach ($this->options as $option) {
            if ($selected == '') {
                $selected = $option['value'];
            }

            if ($this->option_selected == $option['value']) {
                $selected = $option['value'];
            }
        }

        $id = $this->getAttribute('id');
        $counter = 0;
        foreach ($this->options as $option) {
            $counter++; $oid = $id . '-' . $counter;
            $return .= '<p class="radio">';
            $return .= '<input type="radio" class="radio" id="' . $oid . '" name="' . $this->getAttribute('name') . '" value="' . $option['value'] . '" ';
            if ($selected == $option['value']) {
                $return .= ' checked="checked" ';
            }
            $return .= '/>';
            $return .= '<label for="' . $oid . '">' . $option['name'] . '</label>' . "\n";
            $return .= '</p>';
        }

        return $return;

    }

}

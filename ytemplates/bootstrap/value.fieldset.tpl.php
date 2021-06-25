<?php

// exception. Is also called in form.tpl.php, not in field mode
if ('close' == $option) {
    echo '</fieldset>';
    return;
}

$attributes = [
    'class' => $this->getHTMLClass(),
    'id' => $this->getHTMLId(),
];

$attributes = $this->getAttributeElements($attributes, []);

if ('open' == $option) {
    echo '<fieldset '.implode(' ', $attributes).'>';
    if ($this->getLabel()) {
        echo '<legend id="'.$this->getFieldId().'">'.$this->getLabel().'</legend>';
    }
} elseif ('close' == $option) {
    echo '</fieldset>';
}

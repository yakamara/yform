<?php

class rex_yform_choice_group_view
{
    public $label;
    public $choices;

    /**
     * Creates a new choice group view.
     *
     * @param string                  $label   The label of the group
     * @param rex_yform_choice_view[] $choices the choice views in the group
     */
    public function __construct($label, array $choices = [])
    {
        $this->label = $label;
        $this->choices = $choices;
    }

    public function getChoices()
    {
        return $this->choices;
    }

    public function getLabel()
    {
        return $this->label;
    }
}

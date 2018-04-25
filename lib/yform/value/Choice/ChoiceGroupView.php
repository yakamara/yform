<?php

class ChoiceGroupView
{
    public $label;
    public $choices;
    /**
     * Creates a new choice group view.
     *
     * @param string                         $label   The label of the group
     * @param ChoiceGroupView[]|ChoiceView[] $choices the choice views in the group
     */
    public function __construct($label, array $choices = array())
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

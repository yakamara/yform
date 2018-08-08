<?php

class rex_yform_choice_list_view
{
    public $choices;
    public $preferredChoices;

    /**
     * Creates a new choice list view.
     *
     * @param rex_yform_choice_group_view[]|rex_yform_choice_view[] $choices          The choice views
     * @param rex_yform_choice_group_view[]|rex_yform_choice_view[] $preferredChoices the preferred choice views
     */
    public function __construct(array $choices = [], array $preferredChoices = [])
    {
        $this->choices = $choices;
        $this->preferredChoices = $preferredChoices;
    }

    /**
     * Returns whether a placeholder is in the choices.
     *
     * A placeholder must be the first child element, not be in a group and have an empty value.
     *
     * @return bool
     */
    public function hasPlaceholder()
    {
        if ($this->preferredChoices) {
            $firstChoice = reset($this->preferredChoices);
            return $firstChoice instanceof rex_yform_choice_view && '' === $firstChoice->value;
        }
        $firstChoice = reset($this->choices);
        return $firstChoice instanceof rex_yform_choice_view && '' === $firstChoice->value;
    }

    public function getChoices()
    {
        return $this->choices;
    }

    public function getPreferredChoices()
    {
        return $this->preferredChoices;
    }
}

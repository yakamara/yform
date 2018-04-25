<?php

class ChoiceListView
{
    public $choices;
    public $preferredChoices;
    /**
     * Creates a new choice list view.
     *
     * @param ChoiceGroupView[]|ChoiceView[] $choices          The choice views
     * @param ChoiceGroupView[]|ChoiceView[] $preferredChoices the preferred choice views
     */
    public function __construct(array $choices = array(), array $preferredChoices = array())
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
            return $firstChoice instanceof ChoiceView && '' === $firstChoice->value;
        }
        $firstChoice = reset($this->choices);
        return $firstChoice instanceof ChoiceView && '' === $firstChoice->value;
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

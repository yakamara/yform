<?php

namespace Yakamara\YForm\Choice;

class GroupView
{
    public string $label;
    public array $choices;

    /**
     * Creates a new choice group view.
     *
     * @param string                  $label   The label of the group
     * @param array<\Yakamara\YForm\Choice\View> $choices the choice views in the group
     */
    public function __construct(string $label, array $choices = [])
    {
        $this->label = $label;
        $this->choices = $choices;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}

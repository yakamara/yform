<?php

class rex_yform_choice_view
{
    public $label;
    public $value;

    /**
     * Additional attributes for the HTML tag.
     */
    public $attributes;
    public $requiredAttributes;

    /**
     * Creates a new choice view.
     *
     * @param string          $value              The view representation of the choice
     * @param string          $label              The label displayed to humans
     * @param callable|string $attributes         Additional attributes for the HTML tag
     * @param array           $requiredAttributes Required attributes for the HTML tag
     */
    public function __construct($value, $label, $attributes = null, array $requiredAttributes = [])
    {
        $this->value = $value;
        $this->label = $label;

        if (null === $this->attributes) {
            $this->attributes = [];
        } elseif (is_callable($attributes)) {
            $this->attributes = call_user_func($attributes, $this->getValue(), $this->getLabel());
        } elseif (!is_array($attributes)) {
            $this->attributes = json_decode(trim($attributes), true);
        }

        $this->requiredAttributes = $requiredAttributes;

        if (null === $this->attributes) {
            $this->attributes = [];
        }
        // Remove foreign attributes
        foreach ($this->attributes as $index => $attributeValue) {
            if (!is_array($attributeValue)) {
                continue;
            }
            if ($index === $this->getValue()) {
                $this->attributes = array_merge($this->attributes, $attributeValue);
            }
            unset($this->attributes[$index]);
        }
    }

    public function getAttributes()
    {
        return array_merge($this->attributes, $this->requiredAttributes);
    }

    public function getAttributesAsString()
    {
        return \rex_string::buildAttributes($this->getAttributes());
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getValue()
    {
        return $this->value;
    }
}

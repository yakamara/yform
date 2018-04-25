<?php

class ChoiceView
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
     * @param string $value The view representation of the choice
     * @param string $label The label displayed to humans
     * @param callable|string $attributes Additional attributes for the HTML tag
     * @param array $requiredAttributes Required attributes for the HTML tag
     */
    public function __construct($value, $label, $attributes = null, array $requiredAttributes = [])
    {
        $this->value = $value;
        $this->label = $label;
        $this->attributes = is_callable($attributes) ? call_user_func($attributes, $this->getValue(), $this->getLabel()) : json_decode(trim($attributes), true);
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

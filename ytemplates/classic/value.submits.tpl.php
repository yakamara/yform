<p class="formsubmit formsubmits <?php echo $this->getHTMLClass() ?>">

  <?php

  $css_classes = $this->getElement("css_classes");
  if ($css_classes == "") {
    $css_classes = array();
  } else {
    $css_classes = explode(",",$this->getElement("css_classes"));

  }

  $labels = explode(",",$this->getElement("labels"));
  foreach($labels as $label) {

    $classes = array();
    $classes[] = 'submit';

    if ($this->getWarningClass() != "") {
      $classes[] = $this->getWarningClass();
    }

    $value = rex_i18n::translate($label, true);

    $id = $this->getFieldId();

    $key = array_search($label, $labels);
    if ($key !== FALSE && isset($css_classes[$key])) {
      $classes[] = $css_classes[$key];
    }

    echo '<input type="submit" class="'.implode(" ",$classes).'" name="'.$this->getFieldName().'" id="'.$id.'" value="'.$label.'" />';

  }

  ?>

</p>

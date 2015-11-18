<button type="reset" class="btn btn-default<?= trim($this->getElement(4)) != '' ? ' ' . $this->getElement(4) : '' ?>" id="<?= $this->getFieldId() ?>" value="<?= htmlspecialchars(stripslashes($this->getValue())) ?>"><?= htmlspecialchars(stripslashes(rex_i18n::translate($this->getValue()))) ?></button>


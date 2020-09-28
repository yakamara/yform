<?php

if ('docs' == rex_be_controller::getCurrentPagePart(2)) {
    echo rex_view::title($this->i18n('yform'));
}
rex_be_controller::includeCurrentPageSubPath();

<?php

if (rex_be_controller::getCurrentPagePart(2) == "docs") {
    echo rex_view::title($this->i18n('yform'));
}
rex_be_controller::includeCurrentPageSubPath();

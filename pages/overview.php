<?php

echo rex_view::title('yform');

// allgemeiner Infotext
$fragment = new rex_fragment();
$fragment->setVar('class', 'info');
$fragment->setVar('title', rex_i18n::msg('yform_description_title'), false);
$fragment->setVar('body', rex_yform::showHelp(true, true) . rex_i18n::msg('yform_description_all') . rex_yform::showHelp(true, true), false);
echo $fragment->parse('core/page/section.php');


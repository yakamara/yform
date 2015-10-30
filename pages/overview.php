<?php

echo rex_view::title('yform');

$fragment = new rex_fragment();
$fragment->setVar('class', 'info');
$fragment->setVar('title', rex_i18n::msg('yform_description_title'), false);
$fragment->setVar('body', rex_i18n::rawMsg('yform_description_all', false) . rex_yform::showHelp(true, true) , false);
echo $fragment->parse('core/page/section.php');


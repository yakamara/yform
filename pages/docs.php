<?php

$mdFiles = [];
foreach (glob(rex_addon::get('yform')->getPath('docs').'/*.md') as $file) {
    $mdFiles[substr(basename($file), 0, -3)] = $file;
}

$currenMDFile = rex_request('mdfile', 'string', 'intro');
if (!array_key_exists($currenMDFile, $mdFiles)) {
    $currenMDFile = 'intro';
}

$page = \rex_be_controller::getPageObject('yform/docs');

uksort($mdFiles, function($a, $b) {
    $titleA = rex_i18n::msg('yform_docs_'.$a);
    $titleB = rex_i18n::msg('yform_docs_'.$b);
    if ($titleA == $titleB) {
        return 0;
    }
    return ($titleA < $titleB) ? -1 : 1;
});

foreach ($mdFiles as $key => $mdFile) {
    $page->addSubpage((new rex_be_page($key, rex_i18n::msg('yform_docs_'.$key)))
        ->setSubPath($mdFile)
        ->setHref('index.php?page=yform/docs&mdfile='.$key)
        ->setIsActive($key == $currenMDFile)
    );
}

echo rex_view::title($this->i18n('yform'));

[$Toc, $Content] = rex_markdown::factory()->parseWithToc(rex_file::require($mdFiles[$currenMDFile]), 2, 3, false);

preg_match_all('~<code class="language-php">(.*)<\/code>~Usm', $Content, $matches);

foreach($matches[0] as $k => $match) {
    $code = html_entity_decode($matches[1][$k]);
    $code = highlight_string($code, true);
    $Content = str_replace($matches[0][$k], $code, $Content);
}

$fragment = new rex_fragment();
$fragment->setVar('content', $Content, false);
$fragment->setVar('toc', $Toc, false);
$content = $fragment->parse('core/page/docs.php');

$fragment = new rex_fragment();
// $fragment->setVar('title', rex_i18n::msg('package_help') . ' ' . $package->getPackageId(), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');


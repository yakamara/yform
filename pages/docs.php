<?php

use Redaxo\Core\Addon\Addon;
use Redaxo\Core\Backend\Controller;
use Redaxo\Core\Backend\Page;
use Redaxo\Core\Filesystem\File;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\Util\Markdown;
use Redaxo\Core\View\Fragment;
use Redaxo\Core\View\View;

/**
 * @var Addon $this
 * @psalm-scope-this Addon
 */

$mdFiles = [];
foreach (glob(Addon::get('yform')->getPath('docs') . '/*.md') as $file) {
    $mdFiles[mb_substr(basename($file), 0, -3)] = $file;
}

$currenMDFile = Request::request('mdfile', 'string', 'intro');
if (!array_key_exists($currenMDFile, $mdFiles)) {
    $currenMDFile = '01_intro';
}

$page = Controller::getPageObject('yform/docs');

foreach ($mdFiles as $key => $mdFile) {
    $keyWithoudPrio = mb_substr($key, 3);
    $currenMDFileWithoudPrio = mb_substr($currenMDFile, 3);
    $page->addSubpage((new Page($key, I18n::msg('yform_docs_' . $keyWithoudPrio)))
        ->setSubPath($mdFile)
        ->setHref('index.php?page=yform/docs&mdfile=' . $key)
        ->setIsActive($key == $currenMDFile),
    );
}

echo View::title($this->i18n('yform'));

[$Toc, $Content] = Markdown::factory()->parseWithToc(File::require($mdFiles[$currenMDFile]), 2, 3, [
    Markdown::SOFT_LINE_BREAKS => false,
    Markdown::HIGHLIGHT_PHP => true,
]);

$fragment = new Fragment();
$fragment->setVar('content', $Content, false);
$fragment->setVar('toc', $Toc, false);
$content = $fragment->parse('core/page/docs.php');

$fragment = new Fragment();
// $fragment->setVar('title', I18n::msg('package_help') . ' ', false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

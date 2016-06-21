<?php

$lang = 'de_de';

echo rex_view::title(rex_i18n::msg('yform_docs'));

$path = rex_path::plugin('yform','docs','docs/'.$lang.'/');
$navi = rex_file::get($path.'README.md');

$files = [];
foreach(scandir($path) as $i_file) {
    if ($i_file != "." && $i_file != "..") {
        $files[$i_file] = $i_file;

        $search = '#\[(.*)\]\(('.$i_file.')\)#';
        $replace = '<a href="index.php?page=yform/docs&yform_docs_file=$2">$1</a>';

        $navi = preg_replace($search, $replace, $navi);
    }
}



$file = rex_request('yform_docs_file','string','main.md');
if (!in_array($file,$files)) {
    $file = 'main.md';
}

$content = rex_file::get($path.basename($file));
$file_title = ' ['.basename($file).'] ';




if ($content == "") {
    $content = '<p class="alert alert-warning">'.rex_i18n::rawMsg('yform_docs_filenotfound').'</p>';
    $file_title = '';

}

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_docs_navigation'));
$fragment->setVar('body', $navi, false);
$navi = $fragment->parse('core/page/section.php');


$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_docs_content').$file_title);
$fragment->setVar('body', $content, false);
$content = $fragment->parse('core/page/section.php');


echo '<section class="rex-yform-docs">
    <div class="row">
    <div class="col-md-4">'.$navi.'</div>
    <div class="col-md-8">'.$content.'</div>
    </div>
</section>';


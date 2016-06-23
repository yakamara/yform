<?php

$lang = 'de_de';

echo rex_view::title(rex_i18n::msg('yform_docs'));

// refresh
// Umbauen auf zipball... weil nur ein request
// unauthorisiert sind maximal 60 requests die stunde erlaubt, daher dieser weg hier nicht sinnvoll
if (rex_request("yform_docs_func","string") == "refresh") {
    try {
        $files_socket = rex_socket::factoryURL('https://api.github.com/repos/yakamara/redaxo_yform_docs/git/trees/master?recursive=1');
        $response = $files_socket->doGet();
        if($response->isOk()) {
            $files = json_decode($response->getBody(), true);
            $file_path = rex_path::plugin('yform','docs','docs/');
            if (isset($files["tree"]) && is_array($files["tree"])) {
                foreach ($files["tree"] as $file) {
                    if (substr($file["path"],0,6) == "de_de/" && $file["type"] == "blob" && $file["url"] != "") {
                        $file_socket = rex_socket::factoryURL($file["url"]);
                        $response = $file_socket->doGet();
                        if($response->isOk()) {
                            $file_info = json_decode($response->getBody(), true);
                            rex_file::put($file_path.$file["path"], base64_decode($file_info["content"]));
                            echo "*";
                        }
                    }
                }
            }
        }
    } catch(rex_socket_exception $e) {
    }
}

$path = rex_path::plugin('yform','docs','docs/'.$lang.'/');

$files = [];
foreach(scandir($path) as $i_file) {
    if ($i_file != "." && $i_file != "..") {
        $files[$i_file] = $i_file;
    }
}

if (rex_request("yform_docs_image","string") != "" && isset($files[rex_request("yform_docs_image","string")])) {
    ob_end_clean();
    $content = rex_file::get($path.basename(rex_request("yform_docs_image","string")));
    echo $content;
    exit;

}

$navi = rex_file::get($path.'main_navi.md');

$file = rex_request('yform_docs_file','string','yform_intro.md');
if (!in_array($file, $files)) {
    $file = 'main_intro.md';
}
$content = rex_file::get($path.basename($file));
if ($content == "") {
    $content = '<p class="alert alert-warning">'.rex_i18n::rawMsg('yform_docs_filenotfound').'</p>';
}

foreach($files as $i_file) {
    $search = '#\[(.*)\]\(('.$i_file.')\)#';
    $replace = '<a href="index.php?page=yform/docs&yform_docs_file=$2">$1</a>';
    $navi = preg_replace($search, $replace, $navi);

    // ![Alt-Text](bildname.png)
    // ![Ein Screenshot](screenshot.png)
    $search = '#\!\[(.*)\]\(('.$i_file.')\)#';
    $replace = '<img src="index.php?page=yform/docs&yform_docs_image=$2" alt="$1" style="width:100%"/>';
    $content = preg_replace($search, $replace, $content);
}

if (class_exists("rex_markdown")) {

    $miu = new rex_markdown();
    $navi = $miu->parse($navi);

    $miu = new rex_markdown();
    $content = $miu->parse($content);

}

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_docs_navigation').' [ <a href="https://github.com/yakamara/redaxo_yform_docs/tree/master/'.$lang.'/main_navi.md">main_navi.md</a> ] <!-- <span><a href="index.php?page=yform/docs&yform_docs_func=refresh">Refresh</a></span> -->', false);
$fragment->setVar('body', $navi, false);
$navi = $fragment->parse('core/page/section.php');


$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_docs_content').' [ <a href="https://github.com/yakamara/redaxo_yform_docs/tree/master/'.$lang.'/'.basename($file).'">'.basename($file).'</a> ]', false);
$fragment->setVar('body', $content, false);
$content = $fragment->parse('core/page/section.php');


echo '<section class="rex-yform-docs">
    <div class="row">
    <div class="col-md-4">'.$navi.'</div>
    <div class="col-md-8">'.$content.'</div>
    </div>
</section>';
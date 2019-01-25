<?php

foreach ($this->elements as $element) {
    $content_type = '';
    if (isset($element['content_type'])) {
        $content_type = '<div class="type"><b>Content-Type:</b> <span>'.$element['content_type'].'</span></div>';
    }

    $content_body = '';
    if (isset($element['body'])) {
        $content_body = '<div class="body"><b>Body:</b> '.$element['body'].'</div>';
    }

    $content_fields = '';
    if (isset($element['fields'])) {
        $content_fields = '<div class="fields"><b>Fields:</b> '.implode(', ', $element['fields']).'</div>';
    }

    $content_headers = '';
    if (isset($element['headers'])) {
        $content_headers = [];
        foreach ($element['headers'] as $k => $v) {
            $content_headers[] = $k.'='.$v;
        }
        $content_headers = '<div class="fields"><b>Header:</b> '.implode(' / ', $content_headers).'</div>';
    }

    echo '
    <div class="rest-type rest-'.strtolower($element['type']).'">
        <span class="method">'.$element['type'].'</span>
        <span class="path">'.$element['path'].'</span>
        
    </div>
    <div class="rest-parameter">
        '.$content_type.$content_body.$content_fields.$content_headers.'
    </div>';
}

?>


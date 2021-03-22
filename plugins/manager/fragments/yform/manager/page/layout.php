<?php

$searchForm = $this->getVar('searchForm');
$searchList = $this->getVar('searchList');
$detailForm = $this->getVar('detailForm');
$historyPage = $this->getVar('historyPage');
$importPage = $this->getVar('importPage');

$messages = $this->getVar('messages') ?? [];

foreach ($messages as $message) {
    if (!empty($message['link'])) {
        $message['message'] = '<a href="'.$message['link'].'">'.$message['message'].'</a>';
    }

    switch ($message['type']) {
        case 'error':
            echo rex_view::error($message['message']);
            break;
        case 'success':
            echo rex_view::success($message['message']);
            break;
        default:
            echo rex_view::info($message['message']);
    }
}

if ($importPage) {
    echo $importPage;
} elseif ($historyPage) {
    echo $historyPage;
} elseif ($detailForm && $searchForm) {
    echo '<div class="row">';
    echo '<div class="col-sm-3 col-md-3 col-lg-2">' . $searchForm . '</div>';
    echo '<div class="col-sm-9 col-md-9 col-lg-10">' . $detailForm . '</div>';
    echo '</div>';
} elseif ($detailForm) {
    echo $detailForm;
} elseif ($searchForm) {
    echo '<div class="row">';
    echo '<div class="col-sm-3 col-md-3 col-lg-2">' . $searchForm . '</div>';
    echo '<div class="col-sm-9 col-md-9 col-lg-10">' . $searchList . '</div>';
    echo '</div>';
} else {
    echo $searchList;
}

?>

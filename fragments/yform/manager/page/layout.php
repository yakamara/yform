<?php

/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

/** @var rex_yform_manager $manager */
$manager = $this->getVar('this');

/** @var \Yakamara\YForm\Manager\Table\Table $table */
$table = $this->getVar('table');
$detailForm = $this->getVar('detailForm');
$historyPage = $this->getVar('historyPage');
$importPage = $this->getVar('importPage');
$searchForm = $this->getVar('searchForm');

/** @var rex_fragment $searchList */
$searchListFragment = $this->getVar('searchList');
$searchList = $searchListFragment->parse('yform/manager/page/list.php');
// $searchList = $searchListFragment->parse('ymedia/page/grid.php');

/** @var array $messages */
$filterMessages = $this->getVar('filterMessages') ?? [];
if (0 < count($filterMessages)) {
    echo rex_view::info(implode('<br>', $filterMessages), 'rex-yform-filter');
}

/** @var array $messages */
$messages = $this->getVar('messages') ?? [];

foreach ($messages as $message) {
    if (!empty($message['link'])) {
        $message['message'] = '<a href="' . $message['link'] . '">' . $message['message'] . '</a>';
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

<?php

/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

/* @var $manager rex_yform_manager */
$manager = $this->getVar('this');

/* @var $table rex_yform_manager_table */
$table = $this->getVar('table');
$detailForm = $this->getVar('detailForm');
$historyPage = $this->getVar('historyPage');
$importPage = $this->getVar('importPage');

$searchForm = '';
if ($table->isSearchable() && $manager->hasDataPageFunction('search')) {
    $searchForm = $this->getVar('searchForm');
}

$fragment = new rex_fragment();
$fragment->setVar('title', $this->getVar('overview_title'));
$fragment->setVar('options', implode('', $this->getVar('overview_options')), false);
$fragment->setVar('content', $this->getVar('overview_list')->get(), false);
$fragment->setVar('search', $this->getVar('list_search'), false);
$searchList = $fragment->parse('core/page/section.php');

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

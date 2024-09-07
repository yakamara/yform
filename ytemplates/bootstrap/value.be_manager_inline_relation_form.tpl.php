<?php

$prioFieldName ??= '';
$counterfieldkey ??= '';
$form ??= '';

$sortable = false;
$sortButtons = '';
$sorthandle = '';

if ('' != $prioFieldName) {
    $sorthandle = '<span class="sorthandle"></span>';
    $sortButtons = '
            <div class="btn-group btn-group-xs">
             <button type="button" class="btn btn-move" data-yform-be-relation-moveup="' . $counterfieldkey . '" title="move up"><i class="rex-icon rex-icon-up"></i><span class="rex-hidden">⌃</span></button>
             <button type="button" class="btn btn-move" data-yform-be-relation-movedown="' . $counterfieldkey . '" title="move down"><i class="rex-icon rex-icon-down"></i><span class="rex-hidden">⌄</span></button>
            </div>';
}

echo '<div class="row" id="' . $counterfieldkey . '" data-yform-be-relation-item="' . $counterfieldkey . '">
        ' . $sorthandle . '
        <span class="removeadded">
            <div class="btn-group btn-group-xs">
             <button type="button" class="btn btn-default addme" title="add" data-yform-be-relation-add="' . $counterfieldkey . '" data-yform-be-relation-add-position="' . $counterfieldkey . '"><i class="rex-icon rex-icon-add-module"></i><span class="rex-hidden">+</span></button>
             <button type="button" class="btn btn-delete removeme" title="delete" data-yform-be-relation-delete="' . $counterfieldkey . '"><i class="rex-icon rex-icon-delete"></i><span class="rex-hidden">-<span</button>
            </div>
            ' . $sortButtons . '
        </span>
        <div class="yform-be-relation-inline-form">' . $form . '</div>
    </div>';

/**
 * YForm backend behaviour without dependencies: inline editing of has-many relations (also nested ones).
 */
(function () {
    'use strict';

    var counter = 0;

    function refresh(node) {
        // Let REDAXO initialise widgets (e.g. selects) inside added rows.
        window.rex.ready(node);
    }

    /** Direct child of `parent` matching `selector`; nested editors have the same markup and must be skipped. */
    function child(parent, selector) {
        return parent.querySelector(':scope > ' + selector);
    }

    /** Next visible sibling row in the given direction; rows marked for deletion are hidden and skipped. */
    function sibling(row, direction) {
        var node = 'up' === direction ? row.previousElementSibling : row.nextElementSibling;
        while (node && node.hidden) {
            node = 'up' === direction ? node.previousElementSibling : node.nextElementSibling;
        }
        return node;
    }

    document.addEventListener('click', function (event) {
        var add = event.target.closest('[data-yform-inline-add]');
        if (add) {
            var editor = add.closest('[data-yform-inline]');
            var template = child(editor, 'template[data-yform-inline-template]');
            // Each nesting level has its own placeholder, so nested templates inside the row stay intact.
            var placeholder = new RegExp(editor.dataset.yformInlinePlaceholder, 'g');
            var index = 'n' + Date.now().toString(36) + (counter++);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML.replace(placeholder, index);
            var row = wrapper.firstElementChild;
            child(editor, '[data-yform-inline-rows]').appendChild(row);
            refresh(row);
            var first = row.querySelector('input:not([type=hidden]), select, textarea');
            if (first) {
                first.focus();
            }
            return;
        }

        var move = event.target.closest('[data-yform-inline-move]');
        if (move) {
            var moved = move.closest('[data-yform-inline-row]');
            var target = sibling(moved, move.dataset.yformInlineMove);
            if (target) {
                moved.parentNode.insertBefore(moved, 'up' === move.dataset.yformInlineMove ? target : target.nextElementSibling);
                move.focus();
            }
            return;
        }

        var remove = event.target.closest('[data-yform-inline-remove]');
        if (remove) {
            var current = remove.closest('[data-yform-inline-row]');
            var idInput = current.querySelector(':scope > .yform-inline-row-head input[name$="[_id]"]');
            if (idInput && idInput.value) {
                // Existing record: mark for deletion, it is removed when the form is saved.
                current.querySelector(':scope > .yform-inline-row-head [data-yform-inline-delete-flag]').value = '1';
                current.hidden = true;
            } else {
                current.remove();
            }
        }
    });
})();

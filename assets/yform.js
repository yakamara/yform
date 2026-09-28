/**
 * YForm backend behaviour without dependencies: inline editing of has-many relations.
 */
(function () {
    'use strict';

    var counter = 0;

    function refresh(node) {
        // Let REDAXO initialise widgets (selectpicker, media/link widgets) inside added rows.
        if (window.jQuery) {
            window.jQuery(node).trigger('rex:ready', [window.jQuery(node)]);
        }
    }

    document.addEventListener('click', function (event) {
        var add = event.target.closest('[data-yform-inline-add]');
        if (add) {
            var editor = add.closest('[data-yform-inline]');
            var template = editor.querySelector('template[data-yform-inline-template]');
            var index = 'n' + Date.now().toString(36) + (counter++);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML.replace(/__index__/g, index);
            var row = wrapper.firstElementChild;
            editor.querySelector('[data-yform-inline-rows]').appendChild(row);
            refresh(row);
            var first = row.querySelector('input:not([type=hidden]), select, textarea');
            if (first) {
                first.focus();
            }
            return;
        }

        var remove = event.target.closest('[data-yform-inline-remove]');
        if (remove) {
            var current = remove.closest('[data-yform-inline-row]');
            var idInput = current.querySelector('input[name$="[_id]"]');
            if (idInput && idInput.value) {
                // Existing record: mark for deletion, it is removed when the form is saved.
                current.querySelector('[data-yform-inline-delete-flag]').value = '1';
                current.hidden = true;
            } else {
                current.remove();
            }
        }
    });
})();

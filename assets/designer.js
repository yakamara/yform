/**
 * YForm layout designer.
 *
 * State driven: the layout lives as plain object ({tabs: [{label, sections: [{legend, rows: [[{field, width}]]}]}]}),
 * every interaction changes the state and re-renders. Drag and drop uses the native HTML5 API, no dependencies.
 */
(function () {
    'use strict';

    function init(root) {
        if (root.dataset.initialized) {
            return;
        }
        root.dataset.initialized = '1';

        var config = JSON.parse(root.dataset.config);
        var t = config.i18n;
        var fields = {};
        config.fields.forEach(function (field) {
            fields[field.name] = field;
        });

        var state = normalize(config.layout);
        var activeTab = 0;
        var dirty = false;
        var source = config.source;
        var dragged = null; // {field, from: {tab, section, row, cell} | null}

        function normalize(layout) {
            var tabs = (layout.tabs || []).map(function (tab) {
                return {
                    label: tab.label || '',
                    sections: (tab.sections || []).map(function (section) {
                        return {legend: section.legend || '', rows: (section.rows || []).filter(function (row) { return row.length; })};
                    }),
                };
            });
            if (!tabs.length) {
                tabs.push({label: '', sections: []});
            }
            tabs.forEach(function (tab) {
                if (!tab.sections.length) {
                    tab.sections.push({legend: '', rows: []});
                }
            });
            return {tabs: tabs};
        }

        function placedFields() {
            var placed = {};
            state.tabs.forEach(function (tab) {
                tab.sections.forEach(function (section) {
                    section.rows.forEach(function (row) {
                        row.forEach(function (cell) {
                            placed[cell.field] = true;
                        });
                    });
                });
            });
            return placed;
        }

        function changed() {
            dirty = true;
            render();
        }

        function distribute(row) {
            var width = Math.max(1, Math.floor(12 / row.length));
            row.forEach(function (cell) {
                cell.width = width;
            });
        }

        function removeDragged() {
            if (!dragged || !dragged.from) {
                return;
            }
            var f = dragged.from;
            var rows = state.tabs[f.tab].sections[f.section].rows;
            rows[f.row].splice(f.cell, 1);
            if (!rows[f.row].length) {
                rows.splice(f.row, 1);
            } else {
                distribute(rows[f.row]);
            }
        }

        /** Moves the dragged field into a new row that is inserted above row `rowIndex` (`null` or past the end = at the end). */
        function dropNewRow(tabIndex, sectionIndex, rowIndex) {
            if (!dragged) {
                return;
            }
            var section = state.tabs[tabIndex].sections[sectionIndex];
            var before = null === rowIndex ? null : (section.rows[rowIndex] || null);
            var f = dragged.from;
            var cell = {field: dragged.field, width: 12};
            removeDragged();

            var position = section.rows.length;
            if (null !== before) {
                position = section.rows.indexOf(before);
                if (-1 === position) {
                    // The row below was the source row and is gone now: take its place.
                    position = f.row;
                }
            }
            section.rows.splice(position, 0, [cell]);
            dragged = null;
            changed();
        }

        /** Moves the dragged field into an existing row at position `index`. */
        function drop(tabIndex, sectionIndex, rowIndex, index) {
            if (!dragged) {
                return;
            }
            var section = state.tabs[tabIndex].sections[sectionIndex];
            var target = section.rows[rowIndex];
            var f = dragged.from;

            // Adjust the target position when removing the source shifts it.
            if (f && f.tab === tabIndex && f.section === sectionIndex) {
                if (f.row === rowIndex && f.cell < index) {
                    index--;
                }
            }
            var cell = {field: dragged.field, width: 12};
            removeDragged();

            // The target row may have been removed when it only held the dragged cell.
            if (section.rows.indexOf(target) === -1) {
                section.rows.splice(f.row, 0, [cell]);
            } else {
                target.splice(Math.min(index, target.length), 0, cell);
                distribute(target);
            }
            dragged = null;
            changed();
        }

        function moveSection(tab, index, offset) {
            var moved = tab.sections.splice(index, 1)[0];
            tab.sections.splice(index + offset, 0, moved);
            changed();
        }

        function el(tag, attrs, children) {
            var node = document.createElement(tag);
            Object.keys(attrs || {}).forEach(function (key) {
                if ('text' === key) {
                    node.textContent = attrs[key];
                } else if (0 === key.indexOf('on')) {
                    node.addEventListener(key.substring(2), attrs[key]);
                } else if ('style' === key) {
                    // via CSSOM, a style attribute would need `'unsafe-inline'` in the Content-Security-Policy
                    node.style.cssText = attrs[key] || '';
                } else if (null !== attrs[key] && false !== attrs[key]) {
                    node.setAttribute(key, true === attrs[key] ? '' : attrs[key]);
                }
            });
            (children || []).forEach(function (child) {
                if (child) {
                    node.appendChild('string' === typeof child ? document.createTextNode(child) : child);
                }
            });
            return node;
        }

        function dropZone(className, onDrop) {
            var zone = el('div', {'class': className});
            zone.addEventListener('dragover', function (event) {
                if (dragged) {
                    event.preventDefault();
                    zone.classList.add('is-over');
                }
            });
            zone.addEventListener('dragleave', function () {
                zone.classList.remove('is-over');
            });
            zone.addEventListener('drop', function (event) {
                event.preventDefault();
                zone.classList.remove('is-over');
                onDrop();
            });
            return zone;
        }

        /**
         * Upper and lower third of a row: the field becomes a new row above or below it. The middle is left to the
         * gaps between the cells, which add the field to the row itself.
         */
        function rowEdgeDrop(rowNode, tabIndex, sectionIndex, rowIndex) {
            function edge(event) {
                var box = rowNode.getBoundingClientRect();
                var ratio = (event.clientY - box.top) / box.height;
                return ratio < 1 / 3 ? 'before' : (ratio > 2 / 3 ? 'after' : null);
            }
            function clear() {
                rowNode.classList.remove('is-insert-before', 'is-insert-after');
            }
            function over(event) {
                if (!dragged || event.target.closest('.yform-designer-gap')) {
                    clear();
                    return;
                }
                var position = edge(event);
                rowNode.classList.toggle('is-insert-before', 'before' === position);
                rowNode.classList.toggle('is-insert-after', 'after' === position);
                if (position) {
                    event.preventDefault();
                }
            }
            rowNode.addEventListener('dragenter', over);
            rowNode.addEventListener('dragover', over);
            rowNode.addEventListener('dragleave', function (event) {
                if (!rowNode.contains(event.relatedTarget)) {
                    clear();
                }
            });
            rowNode.addEventListener('drop', function (event) {
                if (event.target.closest('.yform-designer-gap')) {
                    return;
                }
                var position = edge(event);
                clear();
                if (position) {
                    event.preventDefault();
                    dropNewRow(tabIndex, sectionIndex, 'before' === position ? rowIndex : rowIndex + 1);
                }
            });
        }

        function fieldChip(fieldName, position, width) {
            var field = fields[fieldName] || {name: fieldName, label: fieldName, type: '?'};
            var chip = el('div', {
                'class': 'yform-designer-cell',
                draggable: 'true',
                style: position ? 'flex: 0 0 calc(' + (width / 12 * 100) + '% - 16px)' : null,
                ondragstart: function (event) {
                    dragged = {field: fieldName, from: position};
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', fieldName);
                    chip.classList.add('is-dragging');
                },
                ondragend: function () {
                    chip.classList.remove('is-dragging');
                    dragged = null;
                },
            }, [
                el('span', {'class': 'yform-designer-grip', 'aria-hidden': 'true'}, ['⠿']),
                el('span', {'class': 'yform-designer-label'}, [
                    el('strong', {text: field.label + (field.required ? ' *' : '')}),
                    el('small', {text: field.name + ' · ' + field.type}),
                ]),
            ]);

            if (position) {
                var select = el('select', {
                    'class': 'rex-input',
                'data-size': 'sm',
                    title: t.width,
                    onchange: function () {
                        state.tabs[position.tab].sections[position.section].rows[position.row][position.cell].width = parseInt(select.value, 10);
                        changed();
                    },
                });
                for (var w = 1; w <= 12; w++) {
                    select.appendChild(el('option', {value: String(w), selected: w === width}, [w + '/12']));
                }
                chip.appendChild(select);
                chip.appendChild(el('button', {
                    type: 'button',
                    'class': 'rex-button',
                    'data-variant': 'link',
                    'data-size': 'xs',
                    title: t.remove,
                    onclick: function () {
                        dragged = {field: fieldName, from: position};
                        removeDragged();
                        dragged = null;
                        changed();
                    },
                }, ['×']));
            }
            return chip;
        }

        function renderSection(tabIndex, section, sectionIndex, tab) {
            var rows = el('div', {'class': 'yform-designer-rows'});
            section.rows.forEach(function (row, rowIndex) {
                rows.appendChild(dropZone('yform-designer-between', function () { dropNewRow(tabIndex, sectionIndex, rowIndex); }));
                var total = row.reduce(function (sum, cell) { return sum + cell.width; }, 0);
                var rowNode = el('div', {'class': 'yform-designer-row' + (total > 12 ? ' is-overflow' : ''), title: total > 12 ? t.row_overflow : null});
                rowEdgeDrop(rowNode, tabIndex, sectionIndex, rowIndex);
                row.forEach(function (cell, cellIndex) {
                    rowNode.appendChild(dropZone('yform-designer-gap', function () { drop(tabIndex, sectionIndex, rowIndex, cellIndex); }));
                    rowNode.appendChild(fieldChip(cell.field, {tab: tabIndex, section: sectionIndex, row: rowIndex, cell: cellIndex}, cell.width));
                });
                rowNode.appendChild(dropZone('yform-designer-gap yform-designer-gap-end', function () { drop(tabIndex, sectionIndex, rowIndex, row.length); }));
                rows.appendChild(rowNode);
            });
            rows.appendChild(dropZone('yform-designer-newrow', function () { dropNewRow(tabIndex, sectionIndex, null); }));
            rows.lastChild.textContent = t.new_row;

            var legend = el('input', {
                'class': 'rex-input yform-designer-legend',
                'data-size': 'sm',
                type: 'text',
                value: section.legend,
                placeholder: t.legend_placeholder,
                oninput: function () { section.legend = legend.value; dirty = true; updateStatus(); },
            });

            return el('div', {'class': 'yform-designer-section'}, [
                el('div', {'class': 'yform-designer-section-head'}, [
                    legend,
                    sectionIndex > 0 ? el('button', {
                        type: 'button',
                        'class': 'rex-button',
                    'data-variant': 'link',
                    'data-size': 'xs',
                        title: t.section_up,
                        onclick: function () { moveSection(tab, sectionIndex, -1); },
                    }, [el('i', {'class': 'rex-icon fa-arrow-up'})]) : null,
                    sectionIndex < tab.sections.length - 1 ? el('button', {
                        type: 'button',
                        'class': 'rex-button',
                    'data-variant': 'link',
                    'data-size': 'xs',
                        title: t.section_down,
                        onclick: function () { moveSection(tab, sectionIndex, 1); },
                    }, [el('i', {'class': 'rex-icon fa-arrow-down'})]) : null,
                    tab.sections.length > 1 ? el('button', {
                        type: 'button',
                        'class': 'rex-button',
                    'data-variant': 'link',
                    'data-size': 'xs',
                        title: t.section_remove,
                        onclick: function () {
                            tab.sections.splice(sectionIndex, 1);
                            changed();
                        },
                    }, [el('i', {'class': 'rex-icon rex-icon-delete'})]) : null,
                ]),
                rows,
            ]);
        }

        var status = el('span', {'class': 'yform-designer-status'});
        var saveButton;

        function updateStatus() {
            var text = 'file' === source ? t.source_file : t.source_code;
            if (dirty) {
                text += ' – ' + t.unsaved;
            }
            status.textContent = text;
            if (saveButton) {
                saveButton.disabled = !config.writable;
            }
        }

        function post(body) {
            var data = new FormData();
            data.append('_csrf_token', config.csrf);
            Object.keys(body).forEach(function (key) {
                data.append(key, body[key]);
            });
            return fetch(config.url, {method: 'POST', body: data, credentials: 'same-origin'}).then(function (response) {
                return response.json().then(function (json) {
                    if (!response.ok || json.error) {
                        throw new Error(json.error || response.statusText);
                    }
                    return json;
                });
            });
        }

        function notify(message, type) {
            var box = root.querySelector('.yform-designer-message');
            box.className = 'yform-designer-message rex-alert';
            box.dataset.variant = type;
            box.textContent = message;
        }

        function render() {
            var placed = placedFields();
            var tab = state.tabs[activeTab] || state.tabs[0];

            var tabNav = el('ul', {'class': 'rex-tabs yform-designer-tabs', role: 'tablist'});
            state.tabs.forEach(function (item, index) {
                var link = el('a', {href: '#', 'class': 'rex-tabs-link', role: 'tab', 'aria-selected': String(index === activeTab), onclick: function (event) { event.preventDefault(); activeTab = index; render(); }}, [item.label || (t.tab + ' ' + (index + 1))]);
                link.addEventListener('dragover', function (event) {
                    if (dragged && index !== activeTab) {
                        event.preventDefault();
                        activeTab = index;
                        render();
                    }
                });
                tabNav.appendChild(el('li', {'class': 'rex-tabs-item', role: 'presentation'}, [link]));
            });
            tabNav.appendChild(el('li', {'class': 'rex-tabs-item', role: 'presentation'}, [el('a', {href: '#', 'class': 'rex-tabs-link', title: t.tab_add, onclick: function (event) {
                event.preventDefault();
                state.tabs.push({label: t.tab + ' ' + (state.tabs.length + 1), sections: [{legend: '', rows: []}]});
                activeTab = state.tabs.length - 1;
                changed();
            }}, ['+'])]));

            var tabLabel = el('input', {
                'class': 'rex-input',
                'data-size': 'sm',
                type: 'text',
                value: tab.label,
                placeholder: t.tab,
                oninput: function () {
                    tab.label = tabLabel.value;
                    tabNav.children[activeTab].firstChild.textContent = tab.label || (t.tab + ' ' + (activeTab + 1));
                    dirty = true;
                    updateStatus();
                },
            });

            var canvas = el('div', {'class': 'yform-designer-canvas'}, [
                el('div', {'class': 'yform-designer-tabhead'}, [
                    tabLabel,
                    state.tabs.length > 1 ? el('button', {type: 'button', 'class': 'rex-button', 'data-size': 'sm', onclick: function () {
                        state.tabs.splice(activeTab, 1);
                        activeTab = Math.max(0, activeTab - 1);
                        changed();
                    }}, [t.tab_remove]) : null,
                ]),
            ]);
            tab.sections.forEach(function (section, sectionIndex) {
                canvas.appendChild(renderSection(activeTab, section, sectionIndex, tab));
            });
            canvas.appendChild(el('button', {type: 'button', 'class': 'rex-button', 'data-size': 'sm', onclick: function () {
                tab.sections.push({legend: '', rows: []});
                changed();
            }}, ['+ ' + t.section_add]));

            var palette = el('div', {'class': 'yform-designer-palette'}, [el('h4', {text: t.palette})]);
            var unplaced = config.fields.filter(function (field) { return !placed[field.name]; });
            unplaced.forEach(function (field) {
                palette.appendChild(fieldChip(field.name, null, 12));
            });
            if (!unplaced.length) {
                palette.appendChild(el('p', {'class': 'rex-u-text-muted', text: t.palette_empty}));
            }
            palette.addEventListener('dragover', function (event) {
                if (dragged && dragged.from) {
                    event.preventDefault();
                    palette.classList.add('is-over');
                }
            });
            palette.addEventListener('dragleave', function () { palette.classList.remove('is-over'); });
            palette.addEventListener('drop', function (event) {
                event.preventDefault();
                palette.classList.remove('is-over');
                removeDragged();
                dragged = null;
                changed();
            });

            saveButton = el('button', {type: 'button', 'class': 'rex-button', 'data-variant': 'success', onclick: function () {
                post({layout: JSON.stringify(state)}).then(function (json) {
                    state = normalize(json.layout);
                    source = json.source;
                    dirty = false;
                    render();
                    notify(json.message, 'success');
                }).catch(function (error) { notify(error.message, 'danger'); });
            }}, [t.save]);

            var toolbar = el('div', {'class': 'yform-designer-toolbar'}, [
                saveButton,
                'file' === source && config.writable ? el('button', {type: 'button', 'class': 'rex-button', onclick: function () {
                    if (!window.confirm(t.reset_confirm)) {
                        return;
                    }
                    post({action: 'reset'}).then(function (json) {
                        state = normalize(json.layout);
                        source = json.source;
                        dirty = false;
                        activeTab = 0;
                        render();
                        notify(json.message, 'success');
                    }).catch(function (error) { notify(error.message, 'danger'); });
                }}, [t.reset]) : null,
                status,
            ]);

            var message = root.querySelector('.yform-designer-message');
            root.innerHTML = '';
            root.appendChild(message || el('div', {'class': 'yform-designer-message'}));
            if (config.readonly) {
                root.appendChild(el('div', {'class': 'rex-alert', 'data-variant': 'warning', text: config.readonly}));
            }
            if ('file' === source && config.overrides_code) {
                root.appendChild(el('div', {'class': 'rex-alert', text: t.overrides_code}));
            }
            root.appendChild(toolbar);
            root.appendChild(el('div', {'class': 'yform-designer-body'}, [
                el('div', {'class': 'yform-designer-main'}, [tabNav, canvas]),
                palette,
            ]));
            updateStatus();
        }

        // A chip removed by a re-render never receives dragend, so reset the drag state on the document as well.
        document.addEventListener('dragend', function () {
            dragged = null;
            root.querySelectorAll('.is-over, .is-insert-before, .is-insert-after').forEach(function (node) {
                node.classList.remove('is-over', 'is-insert-before', 'is-insert-after');
            });
        });

        window.addEventListener('beforeunload', function (event) {
            if (dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        render();
    }

    function boot() {
        var root = document.getElementById('yform-designer');
        if (root) {
            init(root);
        }
    }

    // REDAXO fires rex:ready for the initial page and for markup loaded later on.
    document.addEventListener('rex:ready', boot);
})();

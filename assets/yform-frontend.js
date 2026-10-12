/**
 * YForm frontend behaviour without dependencies: tabs for themes without a framework tab plugin
 * (`Theme::plain()`, `Theme::tailwind()`, own themes with `tabToggle: ''`).
 *
 * Without this script the tab navigation stays hidden and all tabs are shown one below the other.
 */
(function () {
    'use strict';

    function classes(element, attribute) {
        var value = element ? element.getAttribute(attribute) : null;
        return value ? value.split(/\s+/).filter(Boolean) : [];
    }

    function toggle(element, attribute, on) {
        classes(element, attribute).forEach(function (name) {
            element.classList.toggle(name, on);
        });
    }

    function pane(link) {
        return document.getElementById(link.getAttribute('href').slice(1));
    }

    function activate(nav, target) {
        nav.querySelectorAll('[data-yform-tab]').forEach(function (link) {
            var on = link === target;
            link.setAttribute('aria-selected', on ? 'true' : 'false');
            toggle(link, 'data-yform-tab-active', on);
            toggle(link.closest('li'), 'data-yform-tab-active', on);
            var panel = pane(link);
            if (panel) {
                panel.hidden = !on;
                toggle(panel, 'data-yform-tab-pane-active', on);
            }
        });
    }

    function init(root) {
        root.querySelectorAll('[data-yform-tabs]').forEach(function (nav) {
            if (nav.dataset.yformTabsReady) {
                return;
            }
            nav.dataset.yformTabsReady = '1';
            nav.hidden = false;
            nav.querySelectorAll('[data-yform-tab]').forEach(function (link) {
                var panel = pane(link);
                var heading = panel ? panel.querySelector(':scope > .yform-tab-heading') : null;
                if (heading) {
                    heading.hidden = true;
                }
            });
            activate(nav, nav.querySelector('[data-yform-tab][aria-selected="true"]') || nav.querySelector('[data-yform-tab]'));
        });
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-yform-tab]');
        if (link) {
            event.preventDefault();
            activate(link.closest('[data-yform-tabs]'), link);
        }
    });

    // Browser validation can not focus a required field in a hidden tab: open its tab first.
    document.addEventListener('invalid', function (event) {
        var panel = event.target.closest('[role="tabpanel"][hidden]');
        if (!panel) {
            return;
        }
        var link = document.querySelector('[data-yform-tab][href="#' + panel.id + '"]');
        if (link) {
            activate(link.closest('[data-yform-tabs]'), link);
        }
    }, true);

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }
})();

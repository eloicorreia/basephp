(function () {
    'use strict';

    function normalizePath(path) {
        if (!path || path === '/') {
            return '/';
        }

        return path.replace(/\/+$/, '');
    }

    function clearMenuState(sidebar) {
        sidebar.querySelectorAll('[data-admin-menu-link], [data-admin-menu-parent]').forEach(function (element) {
            element.classList.remove('active');

            if (element.hasAttribute('aria-expanded')) {
                element.setAttribute('aria-expanded', 'false');
            }
        });

        sidebar.querySelectorAll('.menu-dropdown').forEach(function (dropdown) {
            dropdown.classList.remove('show');
        });
    }

    function findCurrentLink(sidebar) {
        var currentPath = normalizePath(window.location.pathname);
        var bestLink = null;
        var bestLength = -1;

        sidebar.querySelectorAll('[data-admin-menu-link]').forEach(function (link) {
            var href = link.getAttribute('href');

            if (!href || href === '#') {
                return;
            }

            var url;

            try {
                url = new URL(href, window.location.origin);
            } catch (e) {
                return;
            }

            if (url.origin !== window.location.origin) {
                return;
            }

            var linkPath = normalizePath(url.pathname);

            if (currentPath === linkPath || currentPath.indexOf(linkPath + '/') === 0) {
                if (linkPath.length > bestLength) {
                    bestLink = link;
                    bestLength = linkPath.length;
                }
            }
        });

        return bestLink;
    }

    function markCurrentMenu() {
        var sidebar = document.getElementById('admin-sidebar');

        if (!sidebar) {
            return;
        }

        var bestLink = findCurrentLink(sidebar);

        clearMenuState(sidebar);

        if (!bestLink) {
            return;
        }

        bestLink.classList.add('active');

        var currentDropdown = bestLink.closest('.menu-dropdown');

        while (currentDropdown) {
            currentDropdown.classList.add('show');

            if (currentDropdown.id) {
                var trigger = sidebar.querySelector('[href="#' + CSS.escape(currentDropdown.id) + '"]');

                if (trigger) {
                    trigger.classList.add('active');
                    trigger.setAttribute('aria-expanded', 'true');
                }
            }

            currentDropdown = currentDropdown.parentElement
                ? currentDropdown.parentElement.closest('.menu-dropdown')
                : null;
        }
    }

    function markLayoutReady() {
        document.documentElement.classList.add('admin-layout-ready');
    }

    function refreshAdminNavigation() {
        markCurrentMenu();
        markLayoutReady();
    }

    document.addEventListener('DOMContentLoaded', refreshAdminNavigation);
    document.addEventListener('turbo:load', refreshAdminNavigation);
    document.addEventListener('turbo:render', refreshAdminNavigation);
    document.addEventListener('turbo:before-render', function () {
        document.documentElement.classList.remove('admin-layout-ready');
    });
})();

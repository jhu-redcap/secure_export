(function () {
    const DEFAULT_TOOLTIP = 'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-logging-exports.js');
    function makeBanIcon() {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-ban jhu-ban-icon';
        icon.style.color = '#C00000';
        icon.style.marginRight = '6px';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }
    function isLoggingExportButton(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;

        const onclick = btn.getAttribute('onclick') || '';

        return (
            onclick.includes('Logging/csv_export.php') ||
            onclick.includes('AlertsController:downloadLogs')
        );
    }
    function isLoggingExportButton_orig(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;

        const onclick = btn.getAttribute('onclick') || '';
        return onclick.includes('Logging/csv_export.php');
    }

    function decorateButton(btn, tooltipText) {
        if (!btn || btn.dataset.jhuLoggingBlocked === '1') return;

        btn.dataset.jhuLoggingBlocked = '1';
        btn.setAttribute('title', tooltipText);
        btn.setAttribute('aria-disabled', 'true');
        btn.style.cursor = 'not-allowed';
        btn.style.opacity = '0.65';

        btn.removeAttribute('onclick');

        if (!btn.querySelector('.jhu-ban-icon')) {
            btn.insertBefore(makeBanIcon(), btn.firstChild);
        }
    }

    function blockEvent(e) {
        const btn = e.target.closest('button');
        if (!isLoggingExportButton(btn)) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        return false;
    }

    function scanAndDecorate(tooltipText) {
        document.querySelectorAll('button').forEach(function (btn) {
            if (isLoggingExportButton(btn)) {
                decorateButton(btn, tooltipText);
            }
        });
    }

    function disable(tooltipText) {
        const finalTooltip = (tooltipText && tooltipText.trim()) ? tooltipText : DEFAULT_TOOLTIP;

        scanAndDecorate(finalTooltip);

        document.addEventListener('click', blockEvent, true);
        document.addEventListener('mousedown', blockEvent, true);
        document.addEventListener('mouseup', blockEvent, true);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                blockEvent(e);
            }
        }, true);

        const observer = new MutationObserver(function () {
            scanAndDecorate(finalTooltip);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    window.JHU_LoggingRestrictions = {
        disable: disable
    };
})();
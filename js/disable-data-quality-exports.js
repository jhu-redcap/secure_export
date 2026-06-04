(function () {
    const DEFAULT_TOOLTIP = 'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-data-quality-exports.js');
    function makeBanIcon() {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-ban jhu-ban-icon';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function isDataQualityExportButton(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;

        const onclick = btn.getAttribute('onclick') || '';
        return onclick.includes('DataQuality/resolve_csv_export.php');
    }

    function decorateButton(btn, tooltipText) {
        if (!btn || btn.dataset.jhuDataQualityBlocked === '1') return;

        btn.dataset.jhuDataQualityBlocked = '1';
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
        if (!isDataQualityExportButton(btn)) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        return false;
    }

    function scanAndDecorate(tooltipText) {
        document.querySelectorAll('button').forEach(function (btn) {
            if (isDataQualityExportButton(btn)) {
                decorateButton(btn, tooltipText);
            }
        });
    }

    function disable(tooltipText) {
        const finalTooltip = (tooltipText && tooltipText.trim())
            ? tooltipText
            : DEFAULT_TOOLTIP;

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

    window.JHU_DataQualityRestrictions = {
        disable: disable
    };
})();
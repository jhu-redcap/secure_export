(function () {

    const DEFAULT_TOOLTIP =
        'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-project-xml-export.js');
    function makeBanIcon() {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-ban jhu-ban-icon';
        icon.style.color = '#C00000';
        icon.style.marginRight = '6px';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function isXmlExportButton(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;

        const onclick = btn.getAttribute('onclick') || '';

        return onclick.includes("showExportFormatDialog('ALL',true)");
    }

    function decorateButton(btn, tooltipText) {

        if (btn.dataset.jhuBlocked === '1') return;

        btn.dataset.jhuBlocked = '1';

        btn.setAttribute('title', tooltipText);
        btn.setAttribute('aria-disabled', 'true');

        btn.style.cursor = 'not-allowed';
        btn.style.opacity = '0.65';

        // Remove export handler
        btn.removeAttribute('onclick');

        if (!btn.querySelector('.jhu-ban-icon')) {
            btn.insertBefore(makeBanIcon(), btn.firstChild);
        }
    }

    function blockEvent(e) {

        const btn = e.target.closest('button');

        if (!isXmlExportButton(btn)) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        return false;
    }

    function scanAndDecorate(tooltipText) {

        document.querySelectorAll('button').forEach(function (btn) {

            if (isXmlExportButton(btn)) {
                decorateButton(btn, tooltipText);
            }

        });
    }
    function addBanner(msgHtml) {
        if (!msgHtml || !msgHtml.trim()) return;
        if (document.getElementById('jhu-project-backup-banner')) return;

        const container = document.querySelector('.round.chklist');
        if (!container) return;

        const banner = document.createElement('div');
        banner.id = 'jhu-project-backup-banner';
        banner.className = 'jhu-restriction-banner';
        banner.innerHTML = '<div class="jhu-restriction-banner-inner">' + msgHtml + '</div>';

        const header = container.querySelector('.chklisthdr');

        if (header) {
            header.insertAdjacentElement('afterend', banner);
        } else {
            container.insertBefore(banner, container.firstChild);
        }
    }
    function disable(tooltipText, msgHtml) {

        const finalTooltip =
            tooltipText && tooltipText.trim()
                ? tooltipText
                : DEFAULT_TOOLTIP;

        scanAndDecorate(finalTooltip);
        addBanner(msgHtml);
        document.addEventListener('click', blockEvent, true);
        document.addEventListener('mousedown', blockEvent, true);
        document.addEventListener('mouseup', blockEvent, true);

        const observer = new MutationObserver(function () {
            scanAndDecorate(finalTooltip);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    window.JHU_ProjectXmlRestrictions = {
        disable: disable
    };

})();
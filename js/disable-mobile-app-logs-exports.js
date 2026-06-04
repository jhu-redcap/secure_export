(function () {
    const DEFAULT_TOOLTIP = 'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-mobile-app-logs-exports.js');
    function isLogsTab() {
        return window.location.search.indexOf('logs=1') !== -1;
    }

    function makeBanIcon() {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-ban jhu-ban-icon';
        icon.style.color = '#C00000';
        icon.style.marginRight = '6px';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function isAppLogDownloadButton(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;

        const onclick = btn.getAttribute('onclick') || '';
        const text = (btn.textContent || '').trim().toLowerCase();

        return onclick.includes('DataEntry/file_download.php') && text.includes('download app log');
    }

    function decorateButton(btn, tooltipText) {
        if (!btn || btn.dataset.jhuMobileLogBlocked === '1') return;

        btn.dataset.jhuMobileLogBlocked = '1';
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
        if (!isLogsTab()) return;

        const btn = e.target.closest('button');
        if (isAppLogDownloadButton(btn)) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
    }

    function scanAndDecorate(tooltipText) {
        if (!isLogsTab()) return;

        document.querySelectorAll('button').forEach(function (btn) {
            if (isAppLogDownloadButton(btn)) {
                decorateButton(btn, tooltipText);
            }
        });
    }
    function addBanner(msgHtml) {
        if (!isLogsTab()) return;
        if (!msgHtml || !msgHtml.trim()) return;
        if (document.getElementById('jhu-mobile-app-files-banner')) return;

        const mobileAppHeader = Array.from(document.querySelectorAll('.projhdr'))
            .find(el => el.textContent.includes('REDCap Mobile App'));

        if (!mobileAppHeader){
            module.log('REDCap Mobile App div not found');
            return;
        }

        const banner = document.createElement('div');
        banner.id = 'jhu-mobile-app-files-banner';
        banner.className = 'jhu-restriction-banner';
        banner.style.margin = '15px 0 12px 0';
        banner.style.width = '100%';
        banner.innerHTML = '<div class="jhu-restriction-banner-inner">' + msgHtml + '</div>';

        mobileAppHeader.insertAdjacentElement('afterend', banner);
    }

    function disable(tooltipText, msgHtml) {
        const finalTooltip = (tooltipText && tooltipText.trim())
            ? tooltipText
            : DEFAULT_TOOLTIP;

        scanAndDecorate(finalTooltip);
        addBanner(msgHtml);

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
            addBanner(msgHtml);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    window.JHU_MobileAppLogsRestrictions = {
        disable: disable
    };
})();
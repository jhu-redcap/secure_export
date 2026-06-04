(function () {
    const DEFAULT_TOOLTIP = 'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-mobile-app-files-exports.js');
    function isFilesTab() {
        return window.location.search.indexOf('files=1') !== -1;
    }

    function makeBanIcon() {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-ban jhu-ban-icon';
        icon.style.color = '#C00000';
        icon.style.marginRight = '6px';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function isCsvDownloadButton(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;

        const onclick = btn.getAttribute('onclick') || '';
        const text = (btn.textContent || '').trim().toLowerCase();

        return onclick.includes('DataEntry/file_download.php') && text.includes('download csv');
    }

    function isMobileSubfileDownloadLink(link) {
        if (!link || link.tagName !== 'A') return false;

        const href = link.getAttribute('href') || '';
        return href.includes('DataEntry/file_download.php');
    }

    function decorateButton(btn, tooltipText) {
        if (!btn || btn.dataset.jhuMobileBlocked === '1') return;

        btn.dataset.jhuMobileBlocked = '1';
        btn.setAttribute('title', tooltipText);
        btn.setAttribute('aria-disabled', 'true');
        btn.style.cursor = 'not-allowed';
        btn.style.opacity = '0.65';

        btn.removeAttribute('onclick');

        if (!btn.querySelector('.jhu-ban-icon')) {
            btn.insertBefore(makeBanIcon(), btn.firstChild);
        }
    }

    function decorateLink(link, tooltipText) {
        if (!link || link.dataset.jhuMobileBlocked === '1') return;

        link.dataset.jhuMobileBlocked = '1';
        link.setAttribute('title', tooltipText);
        link.setAttribute('aria-disabled', 'true');
        link.setAttribute('href', 'javascript:;');
        link.style.cursor = 'not-allowed';
        link.style.opacity = '0.65';
        link.style.textDecoration = 'none';

        if (!link.querySelector('.jhu-ban-icon')) {
            link.insertBefore(makeBanIcon(), link.firstChild);
        }
    }

    function blockEvent(e) {
        if (!isFilesTab()) return;

        const btn = e.target.closest('button');
        if (isCsvDownloadButton(btn)) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }

        const link = e.target.closest('a');
        if (isMobileSubfileDownloadLink(link)) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
    }

    function scanAndDecorate(tooltipText) {
        if (!isFilesTab()) return;

        document.querySelectorAll('button').forEach(function (btn) {
            if (isCsvDownloadButton(btn)) {
                decorateButton(btn, tooltipText);
            }
        });

        document.querySelectorAll('a').forEach(function (link) {
            if (isMobileSubfileDownloadLink(link)) {
                decorateLink(link, tooltipText);
            }
        });
    }

    function addBanner(msgHtml) {
        if (!isFilesTab()) return;
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

    window.JHU_MobileAppFilesRestrictions = {
        disable: disable
    };
})();
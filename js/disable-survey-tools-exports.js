(function () {
    const DEFAULT_TOOLTIP = 'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-survey-tools-exports.js');
    function makeBanIcon() {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-ban jhu-ban-icon';
        icon.style.color = '#C00000';
        icon.style.marginRight = '6px';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function isParticipantListExportButton(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;
        const onclick = btn.getAttribute('onclick') || '';

        return onclick.indexOf('Surveys/participant_export.php') !== -1;
    }

    function isInvitationLogExportButton(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;
        const onclick = btn.getAttribute('onclick') || '';
        return onclick.indexOf('Surveys/invitation_log_export.php') !== -1;
    }

    function isBlockedButton(btn) {

        return isParticipantListExportButton(btn) || isInvitationLogExportButton(btn);
    }

    function decorateButton(btn, tooltipText) {
        if (!btn || btn.dataset.jhuSurveyBlocked === '1') return;

        btn.dataset.jhuSurveyBlocked = '1';
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
        if (!isBlockedButton(btn)) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        return false;
    }

    function scanAndDecorate(tooltipText) {
        document.querySelectorAll('button').forEach(function (btn) {
            if (isBlockedButton(btn)) {
                decorateButton(btn, tooltipText);
            }
        });
    }

    function addBanner(msgHtml) {

        if (!msgHtml || !msgHtml.trim()) return;

        // Only show banner on tabs that actually have exports
        const isParticipantTab = window.location.search.indexOf('participant_list=') !== -1;
        const isInvitationLogTab = window.location.search.indexOf('email_log=') !== -1;

        if (!isParticipantTab && !isInvitationLogTab) {
            return; // Do not show on Public Survey Link tab
        }

        if (document.getElementById('jhu-survey-tools-export-banner')) return;

        const banner = document.createElement('div');
        banner.id = 'jhu-survey-tools-export-banner';
        //banner.style.margin = '15px 0 12px 0';
        //banner.style.width = '100%';
        banner.innerHTML = '<div class="jhu-restriction-banner-inner">' + msgHtml + '</div>';
        banner.className = 'jhu-restriction-banner';

        const main = document.querySelector('#partListTitle') || document.body;
        main.insertBefore(banner, main.firstChild);
    }

    function disable(tooltipText, msgHtml) {
        const finalTooltip = (tooltipText && tooltipText.trim()) ? tooltipText : DEFAULT_TOOLTIP;

        scanAndDecorate(finalTooltip);
        //addBanner(msgHtml);

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

    window.JHU_SurveyToolsRestrictions = {
        disable: disable
    };
})();
(function () {
    const DEFAULT_TOOLTIP =
        'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-field-comment-log-export.js');
    function makeBanIcon() {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-ban jhu-ban-icon';
        icon.style.color = '#C00000';
        icon.style.marginRight = '6px';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function isFieldCommentLogExportButton(btn) {
        if (!btn || btn.tagName !== 'BUTTON') return false;

        const onclick = btn.getAttribute('onclick') || '';
        return onclick.includes('DataQuality/field_comment_log_export.php');
    }

    function decorateButton(btn, tooltipText) {
        if (!btn || btn.dataset.jhuFieldCommentBlocked === '1') return;

        btn.dataset.jhuFieldCommentBlocked = '1';
        btn.setAttribute('title', tooltipText);
        btn.setAttribute('aria-disabled', 'true');
        btn.style.cursor = 'not-allowed';
        btn.style.opacity = '0.65';

        btn.removeAttribute('onclick');

        if (!btn.querySelector('.jhu-ban-icon')) {
            btn.insertBefore(makeBanIcon(), btn.firstChild);
        }
    }

    function addBanner(msgHtml) {
        if (!msgHtml || !msgHtml.trim()) return;
        if (document.getElementById('jhu-field-comment-log-banner')) return;

        const banner = document.createElement('div');
        banner.id = 'jhu-field-comment-log-banner';
        banner.className = 'jhu-restriction-banner';
        //banner.style.margin = '0 0 12px 0';
        banner.innerHTML = '<div class="jhu-restriction-banner-inner">' + msgHtml + '</div>';

        const target = document.querySelector('#field_comment_parent');

        if (target && target.parentNode) {
            target.parentNode.insertBefore(banner, target);
            return;
        }

        const fallback =
            document.querySelector('#center') ||
            document.querySelector('.container') ||
            document.querySelector('.container-fluid') ||
            document.body;

        fallback.insertBefore(banner, fallback.firstChild);
    }

    function blockEvent(e) {
        const btn = e.target.closest('button');
        if (!isFieldCommentLogExportButton(btn)) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        return false;
    }

    function scanAndDecorate(tooltipText) {
        document.querySelectorAll('button').forEach(function (btn) {
            if (isFieldCommentLogExportButton(btn)) {
                decorateButton(btn, tooltipText);
            }
        });
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
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    window.JHU_FieldCommentLogRestrictions = {
        disable: disable
    };
})();
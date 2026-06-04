(function () {
    const DEFAULT_TOOLTIP =
        'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-data-quality-rule-exports.js');
    function makeBanIconHtml() {
        return '<i class="fa-solid fa-ban jhu-rule-ban-icon" style="color:#C00000;margin-right:4px;"></i>';
    }

    function getOnclick(el) {
        if (!el) return '';
        return (el.getAttribute('onclick') || '').trim();
    }

    function isDirectRuleExportLink(el) {
        if (!el || el.tagName !== 'A') return false;

        const onclick = getOnclick(el);

        return (
            onclick.startsWith('exportRulesDiscrepancies(') ||
            onclick.startsWith('javascript: exportRulesDiscrepancies(')
        );
    }

    function isViewResultsLink(el) {
        if (!el || el.tagName !== 'A') return false;

        const onclick = getOnclick(el);

        return (
            onclick.startsWith('viewResults(') ||
            onclick.startsWith('javascript:viewResults(') ||
            onclick.startsWith('javascript: viewResults(')
        );
    }

    function isDirectRuleExportButton(el) {
        if (!el || el.tagName !== 'BUTTON') return false;

        const onclick = getOnclick(el);

        return (
            onclick.startsWith('exportRulesDiscrepancies(') ||
            onclick.startsWith('javascript: exportRulesDiscrepancies(')
        );
    }

    function isExecuteTrigger(el) {
        if (!el) return false;

        const onclick = getOnclick(el);
        return onclick.includes('preExecuteRulesAjax(');
    }

    function decorateRuleExportLink(link, tooltipText) {
        if (!link || link.dataset.jhuRuleExportBlocked === '1') return;
        if (isViewResultsLink(link)) return;
        if (!isDirectRuleExportLink(link)) return;

        link.dataset.jhuRuleExportBlocked = '1';
        link.setAttribute('title', tooltipText);
        link.setAttribute('aria-disabled', 'true');
        link.style.cursor = 'not-allowed';
        link.style.color = '#777';
        link.style.textDecoration = 'none';

        const originalOnclick = link.getAttribute('onclick');
        if (originalOnclick) {
            link.dataset.jhuOriginalOnclick = originalOnclick;
        }

        link.removeAttribute('onclick');
        link.setAttribute('href', 'javascript:void(0);');

        if (!link.querySelector('.jhu-rule-ban-icon')) {
            link.insertAdjacentHTML('afterbegin', makeBanIconHtml());
        }
    }

    function decoratePopupExportButtons(tooltipText) {
        $('button').each(function () {
            const $btn = $(this);
            const btn = this;

            if (!isDirectRuleExportButton(btn)) return;
            if ($btn.data('jhuRuleExportBlocked') === 1) return;

            $btn.data('jhuRuleExportBlocked', 1);
            $btn.attr('title', tooltipText);
            $btn.attr('aria-disabled', 'true');
            $btn.css({
                         cursor: 'not-allowed',
                         opacity: '0.65'
                     });

            const originalOnclick = $btn.attr('onclick');
            if (originalOnclick) {
                $btn.attr('data-jhu-original-onclick', originalOnclick);
            }

            $btn.removeAttr('onclick');

            if (!$btn.find('.jhu-rule-ban-icon').length) {
                $btn.prepend(makeBanIconHtml());
            }
        });
    }

    function injectRulesBanner(messageHtml) {
        if (typeof messageHtml !== 'string' || messageHtml.trim() === '') return;

        const bannerId = 'jhu-data-quality-rules-banner';
        let $banner = $('#' + bannerId);

        if (!$banner.length) {
            const $target = $('#rules');
            if (!$target.length) return;

            $banner = $('<div>', {
                id: bannerId,
                class: 'jhu-restriction-banner'
            }).insertBefore($target);
        }

        $banner.html('<div class="jhu-restriction-banner-inner">' + messageHtml + '</div>');
    }

    function applyRestrictions(tooltipText, messageHtml) {
        injectRulesBanner(messageHtml);

        document.querySelectorAll('a').forEach(function (link) {
            if (isDirectRuleExportLink(link)) {
                decorateRuleExportLink(link, tooltipText);
            }
        });

        decoratePopupExportButtons(tooltipText);
    }

    function scheduleApplyBurst(tooltipText, messageHtml) {
        applyRestrictions(tooltipText, messageHtml);
        setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 100);
        setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 300);
        setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 700);
        setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 1200);
        setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 2000);
        setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 3000);
    }

    let jhuDQRulePollingTimer = null;

    function startExecutionPolling(tooltipText, messageHtml) {
        if (jhuDQRulePollingTimer) {
            clearInterval(jhuDQRulePollingTimer);
            jhuDQRulePollingTimer = null;
        }

        let attempts = 0;
        const maxAttempts = 60; // 60 * 250ms = up to 15 seconds

        jhuDQRulePollingTimer = setInterval(function () {
            attempts++;
            applyRestrictions(tooltipText, messageHtml);

            const progressVisible = $('#execRuleProgress:visible').length > 0;
            const completeVisible = $('#execRuleComplete:visible').length > 0;

            if (completeVisible) {
                clearInterval(jhuDQRulePollingTimer);
                jhuDQRulePollingTimer = null;

                // Final cleanup passes after the last rows land
                setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 100);
                setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 400);
                setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 900);
                return;
            }

            if (!progressVisible && attempts >= maxAttempts) {
                clearInterval(jhuDQRulePollingTimer);
                jhuDQRulePollingTimer = null;

                // Last attempt in case REDCap never toggled the indicators properly
                setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 100);
                setTimeout(function () { applyRestrictions(tooltipText, messageHtml); }, 500);
            }
        }, 250);
    }

    function blockEvent(e) {
        const link = e.target.closest('a');
        const button = e.target.closest('button');

        if (link) {
            if (isViewResultsLink(link)) {
                return true;
            }

            const onclick = getOnclick(link);
            const originalOnclick = link.dataset.jhuOriginalOnclick || '';

            if (
                onclick.startsWith('exportRulesDiscrepancies(') ||
                onclick.startsWith('javascript: exportRulesDiscrepancies(') ||
                originalOnclick.startsWith('exportRulesDiscrepancies(') ||
                originalOnclick.startsWith('javascript: exportRulesDiscrepancies(') ||
                link.dataset.jhuRuleExportBlocked === '1'
            ) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
        }

        if (button) {
            const onclick = getOnclick(button);
            const originalOnclick = button.getAttribute('data-jhu-original-onclick') || '';

            if (
                onclick.startsWith('exportRulesDiscrepancies(') ||
                onclick.startsWith('javascript: exportRulesDiscrepancies(') ||
                originalOnclick.startsWith('exportRulesDiscrepancies(') ||
                originalOnclick.startsWith('javascript: exportRulesDiscrepancies(')
            ) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
        }

        return true;
    }

    function disable(tooltipText, messageHtml) {
        const finalTooltip =
            (typeof tooltipText === 'string' && tooltipText.trim() !== '')
                ? tooltipText
                : DEFAULT_TOOLTIP;

        scheduleApplyBurst(finalTooltip, messageHtml);

        $(document)
            .off('click.jhuDQRuleExportBlock')
            .on('click.jhuDQRuleExportBlock', 'a, button', function (e) {
                blockEvent(e);
            });

        $(document)
            .off('mousedown.jhuDQRuleExecuteRefresh')
            .on('mousedown.jhuDQRuleExecuteRefresh', 'button, a', function () {
                if (!isExecuteTrigger(this)) return;

                scheduleApplyBurst(finalTooltip, messageHtml);
                startExecutionPolling(finalTooltip, messageHtml);
            });

        $(document)
            .off('click.jhuDQRuleExecuteRefresh')
            .on('click.jhuDQRuleExecuteRefresh', 'button, a', function () {
                if (!isExecuteTrigger(this)) return;

                scheduleApplyBurst(finalTooltip, messageHtml);
                startExecutionPolling(finalTooltip, messageHtml);
            });

        $(document)
            .off('click.jhuDQRuleViewRefresh')
            .on('click.jhuDQRuleViewRefresh', 'a', function () {
                if (!isViewResultsLink(this)) return;

                setTimeout(function () { applyRestrictions(finalTooltip, messageHtml); }, 75);
                setTimeout(function () { applyRestrictions(finalTooltip, messageHtml); }, 200);
                setTimeout(function () { applyRestrictions(finalTooltip, messageHtml); }, 500);
            });
    }

    window.JHU_DataQualityRuleRestrictions = {
        disable: disable
    };
})();
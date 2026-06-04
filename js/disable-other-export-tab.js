(function () {

    const DEFAULT_TOOLTIP =
        'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-other-export-tab.js');
    function disableOtherExportTab(tooltipText, messageHtml) {

        const finalTooltip =
            (typeof tooltipText === 'string' && tooltipText.trim() !== '')
                ? tooltipText
                : DEFAULT_TOOLTIP;

        function makeBanIcon() {
            const icon = document.createElement('i');
            icon.className = 'fa-solid fa-ban jhu-disabled-export-icon';
            icon.setAttribute('aria-hidden', 'true');
            return icon;
        }

        function injectMessage() {
            if (typeof messageHtml !== 'string' || messageHtml.trim() === '') return;

            const id = 'jhu-other-export-msg';
            let $msg = $('#' + id);

            if (!$msg.length) {
                const $target = $('#report_list_parent_div');
                if (!$target.length) return;

                $msg = $('<div>', {
                    id: id,
                    class: 'jhu-restriction-banner'
                }).insertBefore($target);
            }

            $msg.html(messageHtml);
        }

        function injectSimpleExportBanner() {
            if (typeof messageHtml !== 'string' || messageHtml.trim() === '') return;

            const container = document.getElementById('simple_export');
            if (!container) return;

            let banner = document.getElementById('jhu-simple-export-banner');

            if (!banner) {
                banner = document.createElement('div');
                banner.id = 'jhu-simple-export-banner';
                banner.className = 'jhu-restriction-banner-thin';
                container.insertAdjacentElement('beforebegin', banner);
            }

            banner.innerHTML = messageHtml;
        }

        function injectExportDialogBanner() {
            if (typeof messageHtml !== 'string' || messageHtml.trim() === '') return;

            const dialog = document.querySelector('div[aria-describedby="exportFormatDialog"].ui-dialog');
            if (!dialog) return;

            let banner = dialog.querySelector('#jhu-export-dialog-banner');
            const titlebar = dialog.querySelector('.ui-dialog-titlebar');

            if (!titlebar) return;

            if (!banner) {
                banner = document.createElement('div');
                banner.id = 'jhu-export-dialog-banner';
                banner.className = 'jhu-restriction-banner';
                titlebar.insertAdjacentElement('afterend', banner);
            }

            banner.innerHTML = messageHtml;
        }

        function scheduleDialogBannerInjection() {
            setTimeout(injectExportDialogBanner, 50);
            setTimeout(injectExportDialogBanner, 150);
            setTimeout(injectExportDialogBanner, 300);
        }

        function isBlockedOtherExportLink(link) {
            if (!link || link.tagName !== 'A') return false;

            const href = link.getAttribute('href') || '';
            const onclick = link.getAttribute('onclick') || '';
            const title = link.getAttribute('title') || '';

            return (
                onclick.includes("showExportFormatDialog('ALL',true)") ||
                title.includes('Download ZIP file with uploaded files for all records') ||
                (href.includes('route=PdfController:index') && href.includes('allrecords')) ||
                href.includes('DataExport/data_export_csv.php') && href.includes('type=return_codes')
            );
        }

        function decorateBlockedOtherExportLink(link) {
            if (!link || link.dataset.jhuBlocked === '1') return;

            link.dataset.jhuBlocked = '1';
            link.classList.add('jhu-disabled-link');
            link.setAttribute('title', finalTooltip);
            link.setAttribute('aria-disabled', 'true');
            link.style.cursor = 'not-allowed';

            link.removeAttribute('href');
            link.removeAttribute('onclick');
            link.innerHTML = '';

            const wrapper = document.createElement('span');
            wrapper.className = 'jhu-disabled-export-icon';
            wrapper.setAttribute('title', finalTooltip);
            wrapper.setAttribute('aria-disabled', 'true');
            wrapper.style.cursor = 'not-allowed';
            wrapper.style.display = 'inline-block';
            wrapper.style.verticalAlign = 'middle';

            wrapper.appendChild(makeBanIcon());
            link.appendChild(wrapper);
        }

        function blockOtherExportLinkEvents(e) {
            const link = e.target.closest('#simple_export a');

            if (!isBlockedOtherExportLink(link)) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            return false;
        }

        function scanAndDecorateOtherExportLinks() {
            document.querySelectorAll('#simple_export a').forEach(function (link) {
                if (isBlockedOtherExportLink(link)) {
                    decorateBlockedOtherExportLink(link);
                }
            });
        }

        function applyRestrictions() {
            injectMessage();
            injectSimpleExportBanner();
            scanAndDecorateOtherExportLinks();


        }

        function scheduleApplyBurst() {
            applyRestrictions();
            setTimeout(applyRestrictions, 100);
            setTimeout(applyRestrictions, 300);
            setTimeout(applyRestrictions, 700);
            setTimeout(applyRestrictions, 1200);
        }

        const $exportBtns = $('.data_export_btn');

        if ($exportBtns.length) {
            $exportBtns
                .attr('title', finalTooltip)
                .off('click.jhuBlock')
                .on('click.jhuBlock', function () {
                    scheduleDialogBannerInjection();
                    scheduleApplyBurst();
                });
        }

        document.removeEventListener('click', blockOtherExportLinkEvents, true);
        document.removeEventListener('mousedown', blockOtherExportLinkEvents, true);
        document.removeEventListener('mouseup', blockOtherExportLinkEvents, true);

        document.addEventListener('click', blockOtherExportLinkEvents, true);
        document.addEventListener('mousedown', blockOtherExportLinkEvents, true);
        document.addEventListener('mouseup', blockOtherExportLinkEvents, true);

        scheduleApplyBurst();
    }

    window.JHU_ExportTabRestrictions = {
        disable: disableOtherExportTab
    };

})();
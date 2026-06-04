(function () {
    const DEFAULT_TOOLTIP =
        'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-file-repo-download.js');
    function disableButtons($buttons, tooltipText) {
        const finalTooltip =
            (typeof tooltipText === 'string' && tooltipText.trim() !== '')
                ? tooltipText
                : DEFAULT_TOOLTIP;

        if (!$buttons.length) return;

        $buttons.each(function () {
            const $btn = $(this);

            $btn
                .addClass('jhu-disabled-button')
                .attr({
                          title: finalTooltip,
                          'aria-disabled': 'true'
                      })
                .prop('disabled', false)
                .off('click.jhuBlock')
                .on('click.jhuBlock', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                });
        });
    }

    function disableDownloadButton(tooltipText) {
        const selectors = [
            '#extra-btns button[onclick="downloadMultiple();"]',
            'button[onclick*="doc_id=pdf_archive_all"]',
            'button[onclick*="doc_id=pdf_archive_csv"]'
        ];

        function applyRestriction() {
            selectors.forEach(function (selector) {
                disableButtons($(selector), tooltipText);
            });
        }

        applyRestriction();

        $(document)
            .off('change.jhuFRBlock', '.file-select, .file-select-all')
            .on('change.jhuFRBlock', '.file-select, .file-select-all', function () {
                setTimeout(applyRestriction, 0);
            });

        $(document)
            .off('draw.dt.jhuFRBlock')
            .on('draw.dt.jhuFRBlock', '#file-repository-table', function () {
                setTimeout(applyRestriction, 0);
            });

        if (typeof window.loadFileRepoTable === 'function' && !window.loadFileRepoTable._jhuWrapped) {
            const original = window.loadFileRepoTable;
            window.loadFileRepoTable = function () {
                const result = original.apply(this, arguments);
                setTimeout(applyRestriction, 50);
                return result;
            };
            window.loadFileRepoTable._jhuWrapped = true;
        }
    }

    window.JHU_FileRepoRestrictions = window.JHU_FileRepoRestrictions || {};
    window.JHU_FileRepoRestrictions.disableDownload = disableDownloadButton;
})();
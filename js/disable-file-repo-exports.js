(function () {
    const DEFAULT_TOOLTIP =
        'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
    //console.log('disable-file-repo-exports.js');
    const SELECTORS = {
        bulkDownloadButton: '#extra-btns button[onclick="downloadMultiple();"]',
        fileDownloadLinks: '#file-repository-table a[onclick^="fileRepoDownload("]',
        shareLinks: '#file-repository-table a[onclick^="fileRepoGetPublicLink("]',
        pdfArchiveZipButton: 'button[onclick*="doc_id=pdf_archive_all"]',
        pdfArchiveCsvButton: 'button[onclick*="doc_id=pdf_archive_csv"]',
        fileRepoFolderLinks: 'a[onclick*="loadFileRepoTable("]'
    };

    function normalizeTooltip(tooltipText) {
        return (typeof tooltipText === 'string' && tooltipText.trim() !== '')
            ? tooltipText
            : DEFAULT_TOOLTIP;
    }
    function injectMessageOnce(messageHtml) {
        if (typeof messageHtml !== 'string' || messageHtml.trim() === '') {
            return;
        }

        const id = 'jhu-file-repo-msg';
        if ($('#' + id).length) {
            return;
        }

        const $introParagraph = $('.projhdr')
            .filter(function () {
                return $(this).text().trim() === 'File Repository';
            })
            .next('p');

        if (!$introParagraph.length) {
            return;
        }

        $('<div>', {
            id: id,
            html: messageHtml,
            class: 'jhu-restriction-banner-repo'
        }).insertAfter($introParagraph);
    }
    function injectMessageOnce_orig(messageHtml) {
        if (typeof messageHtml !== 'string' || messageHtml.trim() === '') {
            return;
        }

        const id = 'jhu-file-repo-msg';
        if ($('#' + id).length) {
            return;
        }

        //const $parent = $('#ItemListBreadcrumbParent');
        const $parent = $('#file-repository-space-usage-display');

        if (!$parent.length) {
            return;
        }

        $('<div>', {
            id: id,
            html: messageHtml,
            class: 'jhu-restriction-banner'
        }).insertBefore($parent);
    }

    function blockButton($elements, tooltipText, replacementHtml) {
        if (!$elements.length) return;

        $elements.each(function () {
            const $el = $(this);
            //console.log('blockButton',$el);
            if ($el.data('jhuBlocked')) {
                return;
            }

            $el.data('jhuBlocked', true);

            $el.attr({
                         title: tooltipText,
                         'aria-disabled': 'true'
                     });

            $el.addClass('jhu-disabled-button');
            $el.css({
                        cursor: 'not-allowed',
                        opacity: '0.65',
                        pointerEvents: 'auto'
                    });

            if (replacementHtml && !$el.data('jhuReplacementApplied')) {
                $el.html(replacementHtml);
                $el.data('jhuReplacementApplied', true);
            }

            $el.off('click.jhuBlock').on('click.jhuBlock', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            });

            $el.removeAttr('onclick');
        });
    }
    function normalizeAllowedIds(allowedIds) {
        if (Array.isArray(allowedIds)) {
            return allowedIds
                .flatMap(v => String(v).split(/[\s,]+/))
                .map(v => v.trim())
                .filter(Boolean);
        }

        return String(allowedIds || '')
            .split(/[\s,]+/)
            .map(v => v.trim())
            .filter(Boolean);
    }
    function blockLink($elements, tooltipText, allowedIds = []) {
        if (!$elements.length) return;

        const allowedDocIds = normalizeAllowedIds(allowedIds);

        $elements.each(function () {
            const $el = $(this);

            const id = $el.attr('id');
            const match = id ? id.match(/file-download-(\d+)/) : null;
            const fileId = match ? match[1] : null;

            const docId = $.trim(
                $el.closest('tr').find('div[title="doc_id"]').first().text()
            );

            //console.log('fileId', fileId, 'docId', docId, 'allowedDocIds', allowedDocIds);

            if (docId && allowedDocIds.includes(docId)) {
                return;
            }

            if ($el.data('jhuBlocked')) return;

            $el.data('jhuBlocked', true);

            $el.attr({
                         title: tooltipText,
                         'aria-disabled': 'true',
                         href: 'javascript:;'
                     });

            $el.addClass('jhu-disabled-link').css({
                                                      cursor: 'not-allowed',
                                                      opacity: '0.65',
                                                      textDecoration: 'none'
                                                  });

            $el.off('click.jhuBlock').on('click.jhuBlock', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            });

            $el.removeAttr('onclick');
        });
    }

    function applyRestrictions(tooltipText, messageHtml,blkdownld,blkshare,allowbyid) {
        const finalTooltip = normalizeTooltip(tooltipText);

        injectMessageOnce(messageHtml);

        blockButton(
            $(SELECTORS.bulkDownloadButton),
            finalTooltip,
            '<i class="fa-solid fa-ban" style="color:#C00000;"></i> Export Disabled'
        );
        if(blkdownld === 0) {
            blockLink( $( SELECTORS.fileDownloadLinks ), finalTooltip,allowbyid );
        }
        if(blkshare === 0){
            blockLink($(SELECTORS.shareLinks), finalTooltip,allowbyid);
        }


        blockButton($(SELECTORS.pdfArchiveZipButton), finalTooltip);
        blockButton($(SELECTORS.pdfArchiveCsvButton), finalTooltip);
    }

    function scheduleReapply(tooltipText, messageHtml,blkdownld,blkshare,allowbyid) {
        setTimeout(function () {
            applyRestrictions(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
        }, 0);

        setTimeout(function () {
            applyRestrictions(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
        }, 100);

        setTimeout(function () {
            applyRestrictions(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
        }, 300);

        setTimeout(function () {
            applyRestrictions(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
        }, 700);
    }

    function registerHandlers(tooltipText, messageHtml,blkdownld,blkshare,allowbyid) {
        if (window.__JHU_FILE_REPO_HANDLERS_BOUND__) {
            return;
        }

        window.__JHU_FILE_REPO_HANDLERS_BOUND__ = true;

        $(document)
            .off('click.jhuFileRepoFolder', SELECTORS.fileRepoFolderLinks)
            .on('click.jhuFileRepoFolder', SELECTORS.fileRepoFolderLinks, function () {
                scheduleReapply(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
            });

        $(document)
            .off('draw.dt.jhuFileRepo')
            .on('draw.dt.jhuFileRepo', '#file-repository-table', function () {
                scheduleReapply(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
            });

        $(document)
            .off('change.jhuFileRepo', '.file-select, .file-select-all')
            .on('change.jhuFileRepo', '.file-select, .file-select-all', function () {
                scheduleReapply(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
            });

        if (typeof window.loadFileRepoTable === 'function' && !window.loadFileRepoTable.__jhuWrapped) {
            const originalLoadFileRepoTable = window.loadFileRepoTable;

            const wrapped = function () {
                const result = originalLoadFileRepoTable.apply(this, arguments);
                scheduleReapply(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
                return result;
            };

            wrapped.__jhuWrapped = true;
            window.loadFileRepoTable = wrapped;
        }
    }

    function applyAll(tooltipText, messageHtml,blkdownld,blkshare,allowbyid) {
        applyRestrictions(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
        registerHandlers(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
        scheduleReapply(tooltipText, messageHtml,blkdownld,blkshare,allowbyid);
    }

    window.JHU_FileRepoRestrictions = window.JHU_FileRepoRestrictions || {};
    window.JHU_FileRepoRestrictions.applyAll = applyAll;
})();
(function () {
    const DEFAULT_TOOLTIP = 'Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. Export features are disabled.';
//console.log('pdf-drpdwn-restrictions.js');
    const BLOCKED_LANG_KEYS = new Set([
                                          // Survey/data options only
                                          'data_entry_314',
                                          'data_entry_315',
                                          'data_entry_425',
                                          // Data entry form saved-data options
                                          'data_entry_544',
                                          'data_entry_546',
                                          'data_entry_548',
                                          'data_entry_552',
                                          'data_entry_554',
                                          // Survey saved-data options
                                          'data_entry_547',
                                          'data_entry_549',
                                          'data_entry_553',
                                          'data_entry_555'
                                      ]);

    function makeBanIcon() {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-ban jhu-ban-icon';
        icon.style.color = '#C00000';
        icon.style.marginRight = '6px';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    }

    function isBlockedAnchor(anchor) {
        if (!anchor) return false;

        const href = (anchor.getAttribute('href') || '').toLowerCase();
        const onclick = (anchor.getAttribute('onclick') || '').toLowerCase();
        const text = (anchor.textContent || '').trim().toLowerCase();

        // Direct file export/downloads
        if (href.includes('dataexport/file_export_zip.php')) return true;
        if (onclick.includes('dataexport/file_export_zip.php')) return true;

        // Browser print / save as pdf option
        if (onclick.includes('window.print()')) return true;

        // PDF controller routes: block only saved-data variants, not blank
        if (onclick.includes('route=pdfcontroller:index')) {
            if (
                onclick.includes('&id=') ||
                onclick.includes('&compact=1') ||
                text.includes('with saved data') ||
                text.includes('save as pdf')
            ) {
                return true;
            }
        }

        // Language key fallback
        const spans = anchor.querySelectorAll('span[data-rc-lang]');
        for (const span of spans) {
            const langKey = span.getAttribute('data-rc-lang') || '';
            if (BLOCKED_LANG_KEYS.has(langKey)) {
                return true;
            }
        }

        // Text fallback
        if (
            text.includes('save as pdf') ||
            text.includes('with saved data') ||
            text.includes('all data entry forms with saved data') ||
            text.includes('all forms/surveys with saved data') ||
            text.includes('this data entry form with saved data') ||
            text.includes('this survey with saved data')
        ) {
            return true;
        }

        return false;
    }

    function styleBlockedAnchor(anchor, tooltipText) {
        if (!anchor || anchor.dataset.jhuPdfBlocked === '1') return;

        anchor.dataset.jhuPdfBlocked = '1';
        anchor.classList.add('jhu-disabled-option');
        anchor.setAttribute('title', tooltipText);
        anchor.setAttribute('aria-disabled', 'true');
        anchor.style.opacity = '0.55';
        anchor.style.cursor = 'not-allowed';
        anchor.style.pointerEvents = 'auto';
        anchor.style.textDecoration = 'none';

        try {
            anchor.removeAttribute('onclick');
            anchor.setAttribute('href', 'javascript:void(0);');
            anchor.setAttribute('target', '_self');
        } catch (err) {
            // do nothing
        }

        if (!anchor.querySelector('.jhu-ban-icon')) {
            anchor.insertBefore(makeBanIcon(), anchor.firstChild);
        }
    }

    function scanAndMark(tooltipText) {
        document.querySelectorAll('#pdfExportDropdown a, #pdfExportDropdownDiv a, #recordActionDropdown a')
            .forEach(function (anchor) {
                if (isBlockedAnchor(anchor)) {
                    styleBlockedAnchor(anchor, tooltipText);
                }
            });
    }

    function installBlocker(tooltipText) {
        const finalTooltip = tooltipText && tooltipText.trim() ? tooltipText : DEFAULT_TOOLTIP;

        function shouldBlockFromTarget(target) {
            if (!target) return null;

            const anchor = target.closest('#pdfExportDropdown a, #pdfExportDropdownDiv a, #recordActionDropdown a');
            if (!anchor) return null;

            return isBlockedAnchor(anchor) ? anchor : null;
        }

        function blockEvent(e) {
            const anchor = shouldBlockFromTarget(e.target);
            if (!anchor) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            styleBlockedAnchor(anchor, finalTooltip);
            return false;
        }

        document.addEventListener('click', blockEvent, true);
        document.addEventListener('mousedown', blockEvent, true);
        document.addEventListener('mouseup', blockEvent, true);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                blockEvent(e);
            }
        }, true);

        // Initial pass
        scanAndMark(finalTooltip);

        // Re-scan after the PDF menu button is clicked/opened
        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('#pdf_export_btn, #pdfExport, button[aria-haspopup="true"], .jqbuttonmed');
            if (trigger) {
                setTimeout(function () {
                    scanAndMark(finalTooltip);
                }, 0);
            }
        }, true);
    }

    window.JHU_PdfRestrictions = {
        disable: installBlocker
    };
})();
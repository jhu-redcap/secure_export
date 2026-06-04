<?php
    /** @var \ExternalModules\AbstractExternalModule $module */
    if (!defined("USERID")) exit("Not authenticated.");
    if (!$module->isSuperUser()) exit("Not a Super User.");
    $module->initializeJavascriptModuleObject();
?>

<div class="container-fluid my-3" style="max-width: 1150px;">
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h4 class="mb-1">Environment Report (Server + Client)</h4>
                    <div class="text-muted small">
                        Click <b>Run Report</b> to collect browser info and trigger the server email via <code>collectClientInfo</code>.
                    </div>
                </div>
                <div class="mt-2 mt-md-0">
                    <span id="statusBadge" class="badge bg-secondary">Idle</span>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label mb-1">Label (optional)</label>
                    <input id="envLabel" type="text" class="form-control"
                           placeholder="e.g., Normal workstation, Safe Desktop, Citrix, Horizon...">
                    <div class="form-text">Use a label to compare runs (it will be sent with the AJAX payload).</div>
                </div>
                <div class="col-md-4">
                    <button id="sendEnvReportBtn" class="btn btn-primary w-100" type="button">
                        Run Report (Send Email + Display)
                    </button>
                </div>
            </div>

            <div id="envAlert" class="alert d-none mt-3" role="alert"></div>

            <!-- Hidden until button click -->
            <div id="resultsWrap" class="d-none mt-4">

                <div class="row g-3">
                    <div class="col-lg-4">

                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="mb-2">Run Summary</h6>
                                <div class="small">
                                    <div><span class="text-muted">Label:</span> <span id="metaLabel">—</span></div>
                                    <div><span class="text-muted">Page:</span> <span id="metaPage">—</span></div>
                                    <div><span class="text-muted">Time zone:</span> <span id="metaTz">—</span></div>
                                </div>
                                <hr class="my-2">
                                <div class="small text-muted">
                                    “Highlights” are heuristic and meant for discovery (proxy/VDI/vendor-ish signals).
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">Highlights (Unusual / Interesting)</h6>
                            </div>
                            <div class="card-body">
                                <div id="highlightsBox" class="small text-muted">
                                    No results yet.
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">Server note</h6>
                            </div>
                            <div class="card-body small text-muted">
                                Server headers/IP are not directly readable by the browser. They are captured server-side and included
                                in the emailed report. The “Raw report” tab shows the server-composed report returned by AJAX.
                            </div>
                        </div>

                    </div>

                    <div class="col-lg-8">

                        <ul class="nav nav-tabs" id="envTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-client" data-bs-toggle="tab" data-bs-target="#pane-client" type="button" role="tab">
                                    Client (Browser)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-raw" data-bs-toggle="tab" data-bs-target="#pane-raw" type="button" role="tab">
                                    Raw report (Server + Client)
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content border border-top-0 rounded-bottom p-3" style="min-height: 420px;">
                            <div class="tab-pane fade show active" id="pane-client" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small text-muted">Browser-collected values (rendered as a table for readability).</div>
                                    <input id="clientFilter" class="form-control form-control-sm" style="max-width: 260px;" placeholder="Filter client values…">
                                </div>
                                <div id="clientTable"></div>
                            </div>

                            <div class="tab-pane fade" id="pane-raw" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small text-muted">This is the text your server handler returns (and also emails).</div>
                                    <div class="d-flex gap-2">
                                        <button id="copyRawBtn" class="btn btn-outline-secondary btn-sm" type="button" disabled>Copy</button>
                                        <button id="toggleWrapBtn" class="btn btn-outline-secondary btn-sm" type="button" disabled>Toggle wrap</button>
                                    </div>
                                </div>
                                <pre id="ajaxRespPre" class="bg-light border rounded p-2 mb-0" style="white-space: pre-wrap;"></pre>
                            </div>
                        </div>

                    </div>
                </div>

            </div><!-- /resultsWrap -->

        </div>
    </div>
</div>

<script>
    const module = <?= $module->getJavascriptModuleObjectName() ?>;

    // Things that often indicate proxy/VDI/gateway vendors (heuristic)
    const VENDORISH_RE = /(citrix|netscaler|adc|vmware|horizon|imprivata|safe|vdi|zscaler|cloudflare|akamai|f5|bigip|prisma|paloalto|fortinet|pan-|proxy|forwarded|via)/i;

    let lastClientInfo = null;
    let lastRawReport = "";

    function setBadge(kind, text) {
        const b = document.getElementById("statusBadge");
        b.className = "badge " + kind;
        b.textContent = text;
    }

    function setAlert(type, msg) {
        const el = document.getElementById("envAlert");
        el.className = "alert alert-" + type;
        el.textContent = msg;
        el.classList.remove("d-none");
    }

    function clearAlert() {
        const el = document.getElementById("envAlert");
        el.classList.add("d-none");
        el.textContent = "";
    }

    function esc(s) {
        return String(s ?? "").replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    function buildClientInfo() {
        const nav = window.navigator || {};
        const scr = window.screen || {};
        const tz = (() => {
            try { return Intl.DateTimeFormat().resolvedOptions().timeZone; }
            catch (e) { return ""; }
        })();

        return {
            // Location
            "location.href (window.location.href)": location.href,
            "document.referrer (document.referrer)": document.referrer || "",

            // Navigator
            "navigator.userAgent (navigator.userAgent)": nav.userAgent || "",
            "navigator.platform (navigator.platform)": nav.platform || "",
            "navigator.vendor (navigator.vendor)": nav.vendor || "",
            "navigator.language (navigator.language)": nav.language || "",
            "navigator.languages (navigator.languages)": nav.languages || [],

            // Locale/session-ish
            "Intl.DateTimeFormat().resolvedOptions().timeZone": tz,
            "navigator.cookieEnabled (navigator.cookieEnabled)": !!nav.cookieEnabled,

            // Hardware-ish hints
            "navigator.hardwareConcurrency (navigator.hardwareConcurrency)": nav.hardwareConcurrency ?? null,
            "navigator.deviceMemory (navigator.deviceMemory)": nav.deviceMemory ?? null,
            "navigator.maxTouchPoints (navigator.maxTouchPoints)": nav.maxTouchPoints ?? null,

            // Posture-ish hints
            "navigator.webdriver (navigator.webdriver)": nav.webdriver ?? null,
            "navigator.pdfViewerEnabled (navigator.pdfViewerEnabled)": nav.pdfViewerEnabled ?? null,

            // Display
            "window.devicePixelRatio (window.devicePixelRatio)": window.devicePixelRatio ?? null,
            "screen.width (screen.width)": scr.width ?? null,
            "screen.height (screen.height)": scr.height ?? null,
            "screen.availWidth (screen.availWidth)": scr.availWidth ?? null,
            "screen.availHeight (screen.availHeight)": scr.availHeight ?? null,
            "screen.colorDepth (screen.colorDepth)": scr.colorDepth ?? null,
            "screen.pixelDepth (screen.pixelDepth)": scr.pixelDepth ?? null
        };
    }

    function normalizeVal(v) {
        if (v === null) return "(null)";
        if (v === true) return "true";
        if (v === false) return "false";
        if (typeof v === "object") return JSON.stringify(v);
        return String(v);
    }

    function clientRows(clientInfo) {
        const rows = Object.entries(clientInfo || {}).map(([k, v]) => [k, normalizeVal(v)]);
        rows.sort((a,b) => a[0].localeCompare(b[0]));
        return rows;
    }

    function renderClientTable(rows) {
        let html = `<div class="table-responsive"><table class="table table-sm table-striped align-middle mb-0">
      <thead><tr><th style="width: 45%;">Key</th><th>Value</th></tr></thead><tbody>`;

        for (const [k, v] of rows) {
            const isInteresting =
                VENDORISH_RE.test(k) ||
                (k.includes("navigator.webdriver") && v === "true") ||
                (k.includes("navigator.languages") && v !== "[]");

            const rowClass = isInteresting ? "table-warning" : "";
            const badge = isInteresting ? `<span class="badge bg-warning text-dark ms-2">flag</span>` : "";

            html += `<tr class="${rowClass}">
        <td><code>${esc(k)}</code>${badge}</td>
        <td style="word-break: break-word;">${esc(v)}</td>
      </tr>`;
        }

        html += `</tbody></table></div>`;
        document.getElementById("clientTable").innerHTML = html;
    }

    function buildHighlights(clientInfo, rawReport) {
        const list = [];

        // Client-based highlights
        for (const [k, v0] of Object.entries(clientInfo || {})) {
            const v = normalizeVal(v0);
            if (VENDORISH_RE.test(k)) {
                list.push({key: k, value: v, reason: "Key name looks vendor/proxy/VDI related"});
            }
            if (k.includes("navigator.webdriver") && v === "true") {
                list.push({key: k, value: v, reason: "webdriver=true is uncommon for normal users"});
            }
        }

        // Raw report highlights (server side): look for common proxy headers being present
        if (rawReport) {
            const lines = rawReport.split("\n");
            const interestingServerKeys = [
                "HTTP_X_FORWARDED_FOR",
                "HTTP_X_REAL_IP",
                "X-Forwarded-For",
                "X-Real-Ip",
                "Via:",
                "Forwarded:"
            ];
            for (const line of lines) {
                for (const key of interestingServerKeys) {
                    if (line.toLowerCase().includes(key.toLowerCase()) && !line.endsWith("(null)") && !line.endsWith("= (null)")) {
                        // Keep it short
                        list.push({key: key, value: line.trim(), reason: "Often blank; indicates proxy/gateway path"});
                    }
                }
                if (VENDORISH_RE.test(line)) {
                    list.push({key: "Vendor-ish header/value", value: line.trim(), reason: "Looks like VDI/proxy/vendor signal"});
                }
            }
        }

        return list.slice(0, 25);
    }

    function renderHighlights(items) {
        const box = document.getElementById("highlightsBox");
        if (!items || items.length === 0) {
            box.className = "small text-muted";
            box.textContent = "No obvious highlights detected (heuristic).";
            return;
        }

        let html = `<div class="list-group">`;
        for (const it of items) {
            html += `
        <div class="list-group-item">
          <div class="d-flex justify-content-between">
            <div><b>${esc(it.key)}</b></div>
          </div>
          <div class="small text-muted">${esc(it.reason)}</div>
          <div class="mt-1"><code style="white-space: pre-wrap;">${esc(it.value)}</code></div>
        </div>`;
        }
        html += `</div>`;
        box.className = "";
        box.innerHTML = html;
    }

    async function sendEnvReport() {
        clearAlert();
        setBadge("bg-primary", "Running…");

        const btn = document.getElementById("sendEnvReportBtn");
        btn.disabled = true;
        btn.textContent = "Running…";

        try {
            // show results area only after click
            document.getElementById("resultsWrap").classList.remove("d-none");

            const label = document.getElementById("envLabel").value || "";
            const clientInfo = buildClientInfo();
            lastClientInfo = clientInfo;

            // summary
            document.getElementById("metaLabel").textContent = label || "—";
            document.getElementById("metaPage").textContent = window.location.pathname + window.location.search;
            document.getElementById("metaTz").textContent =
                clientInfo["Intl.DateTimeFormat().resolvedOptions().timeZone"] || "—";

            // render client table
            renderClientTable(clientRows(clientInfo));

            // payload must be scalar values only
            const payload = {
                label: label,
                clientInfoJson: JSON.stringify(clientInfo)
            };

            const resp = await module.ajax("collectClientInfo", payload);

            // show raw report (server-composed)
            lastRawReport = (resp && resp.report) ? resp.report : ((typeof resp === "string") ? resp : JSON.stringify(resp, null, 2));
            document.getElementById("ajaxRespPre").textContent = lastRawReport;

            // enable copy + wrap toggle
            document.getElementById("copyRawBtn").disabled = false;
            document.getElementById("toggleWrapBtn").disabled = false;

            // highlights
            renderHighlights(buildHighlights(clientInfo, lastRawReport));

            setAlert("success", "Report displayed and email triggered successfully.");
            setBadge("bg-success", "Complete");

        } catch (err) {
            console.error(err);
            document.getElementById("ajaxRespPre").textContent = String(err);
            setAlert("danger", "Error running report. Open browser console and check server logs.");
            setBadge("bg-danger", "Error");
        } finally {
            btn.disabled = false;
            btn.textContent = "Run Report (Send Email + Display)";
        }
    }

    document.getElementById("sendEnvReportBtn").addEventListener("click", sendEnvReport);

    // Start blank until click
    document.getElementById("clientTable").innerHTML =
        `<div class="text-muted small">Click <b>Run Report</b> to populate results.</div>`;
    document.getElementById("ajaxRespPre").textContent = "";

    // Filter client table
    document.getElementById("clientFilter").addEventListener("input", function (e) {
        if (!lastClientInfo) return;
        const q = (e.target.value || "").toLowerCase();
        const rows = clientRows(lastClientInfo).filter(([k, v]) =>
                                                           k.toLowerCase().includes(q) || String(v).toLowerCase().includes(q)
        );
        renderClientTable(rows);
    });

    // Copy raw report
    document.getElementById("copyRawBtn").addEventListener("click", async function () {
        try {
            await navigator.clipboard.writeText(lastRawReport || "");
            setAlert("info", "Copied raw report to clipboard.");
        } catch (e) {
            setAlert("warning", "Could not copy automatically. Select the text in Raw report and copy manually.");
        }
    });

    // Toggle wrap for raw report
    document.getElementById("toggleWrapBtn").addEventListener("click", function () {
        const pre = document.getElementById("ajaxRespPre");
        pre.style.whiteSpace = (pre.style.whiteSpace === "pre") ? "pre-wrap" : "pre";
    });
</script>

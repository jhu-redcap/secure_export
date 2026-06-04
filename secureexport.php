<?php

namespace JHU\secureexport;

use ExternalModules\AbstractExternalModule;
use REDCap;

class secureexport extends AbstractExternalModule
{
    private string $defaultTooltip = "Institutional policies limit data exports/downloads to Secure Research Environments (SRE's). Your current IP address is not within an approved SRE IP range. Export features are disabled.";

    private string $defaultBannerHtml = '<div style="background-color: #ffcccc; border: 2px solid #024677; border-radius: 10px; font-weight: 400; padding: 8px 6px 8px 6px; margin: 5px 0px 5px 0px;">
<p><span style="color: #ba372a; font-size: small;"><span style="font-size: large; font-weight: bold;">NOTICE:</span></span></p>
<ul>
<li><strong>Institutional policies limit data exports/downloads to Secure Research Environments (SRE\'s). Your current IP address is not within an approved SRE IP range. As a result, export features (exporting data, logs, PDF\'s with data, etc.) may be disabled for your current session. To export data, you will need to work from within an SRE. For additional information, please reach out to your study team lead or your REDCap Administrator.</strong></li>
</ul>
</div>';
    function getMessageHtmlSetting()
    {
        $message = trim((string) $this->getSystemSetting('export-page-msg'));

        // If no system setting exists, use the default banner HTML
        if ($message === '') {
            return $this->defaultBannerHtml;
        }

        $baseMessage = '
        <div style="background-color: #ffcccc; border: 2px solid #024677; border-radius: 10px; font-weight: 400; padding: 8px 6px 8px 6px; margin: 5px 0px 5px 0px;">placeholder</div>';

        return str_replace('placeholder', $message, $baseMessage);
    }
    function settingCheck($sysset, $projset)
    {
        //this function takes in both the system setting and the project setting and returns true/false based on the logic discussed
        $sysblockExport = $this->getSystemSetting($sysset);
        $projpermitexport = $this->getProjectSetting($projset);

        // Normalize values to integers since the checkbox will return 1 if checked
        $sysblockExport = (int) $sysblockExport;
        $projpermitexport = (int) $projpermitexport;

        // If system setting is empty or null → do NOT block
        if (empty($sysblockExport)) {
            return false;
        }

        // If system says block (1), but project override allows '1' → do NOT block
        if ($sysblockExport === 1 && $projpermitexport === 1) {
            return false;
        }

        // If system says block (1) and project does NOT override → block
        if ($sysblockExport === 1) {
            return true;
        }

        return false;
    }
    function redcap_every_page_top($project_id)
    {

        // Block Items on the Data Export page
        if (PAGE == 'DataExport/index.php')
        {

            if (!$this->checkRestrictions('report-export-sys', 'report-export-prj') && !isset($_GET['other_export_options'])) {
                return;
            }else {

                if ( isset($_GET['other_export_options']) && $_GET['other_export_options'] == 1 ) {
                    if ( !$this->checkRestrictions('other-export-tab-sys', 'other-export-tab-prj') ) {
                        return;
                    }
                }
            }

            //$isRestricted = $this->checkRestrictions();
            $this->includeJs('js/disable-other-export-tab.js');
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$msgHtml = $this->getSystemSetting('export-page-msg') ?: $this->defaultBannerHtml;
            $msgHtml = $this->getMessageHtmlSetting();
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {

                    const tooltipText = <?= json_encode($tooltip) ?>;
                    const msgHtml = <?= json_encode($msgHtml) ?>;

                    //if (isRestricted && window.JHU_ExportTabRestrictions?.disable) {
                    if (window.JHU_ExportTabRestrictions?.disable) {
                        window.JHU_ExportTabRestrictions.disable(tooltipText, msgHtml);
                    }
                });
            </script>
            <?php


                $this->handleSendItUpload($tooltip,$msgHtml);

            return;
        }

        // Block File Repository download button and links
        if (PAGE == 'FileRepositoryController:index')
        {

            $blkdownld = 0;
            $blkshare = 0;
            if (!$this->checkRestrictions('file-repo-download-sys', 'file-repo-download-prj')) {
                $blkdownld = 1;
            }
            if (!$this->checkRestrictions('file-repo-share-sys', 'file-repo-share-prj')) {
                $blkshare = 1;
            }
            if ($blkshare == 1 && $blkdownld == 1) {
                return;
            }
            //if (!$this->checkRestrictions('file-repo-download-sys', 'file-repo-download-prj')) {
            //    return;
            //}
            //if (!$this->checkRestrictions('file-repo-share-sys', 'file-repo-share-prj')) {
            //    return;
            //}
            //$isRestricted = $this->checkRestrictions();
            $this->includeJs('js/disable-file-repo-exports.js');
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$msgHtml = $this->getSystemSetting('export-page-msg') ?: $this->defaultBannerHtml;
            $msgHtml = $this->getMessageHtmlSetting();
            $allowbyid = $this->getProjectSetting('doc-id-exclusion') ?: [];
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    const blkdownld = <?= json_encode($blkdownld) ?>;
                    const blkshare = <?= json_encode($blkshare) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;
                    const msgHtml = <?= json_encode($msgHtml) ?>;
                    const allowbyid = <?= json_encode($allowbyid) ?>;
                    //console.log('allowbyid',allowbyid);
                    //if (isRestricted) {
                        window.JHU_FileRepoRestrictions?.applyAll?.(tooltipText, msgHtml,blkdownld,blkshare,allowbyid);
                    //}
                });
            </script>
            <?php
            return;
        }
        // Block Alert logging
        if (PAGE == 'AlertsController:setup' )
        {
            if (!$this->checkRestrictions('alerts-log-export-sys', 'alerts-log-export-prj')) {
                return;
            }
            //$isRestricted = $this->checkRestrictions();

            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;

            $this->includeJs('js/disable-logging-exports.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;

                    //if (isRestricted && window.JHU_LoggingRestrictions?.disable) {
                    if (window.JHU_LoggingRestrictions?.disable) {
                        window.JHU_LoggingRestrictions.disable(tooltipText);
                    }
                });
            </script>
            <?php
            return;
        }
        // Block Logging page CSV export buttons
        if (PAGE == 'Logging/index.php')
        {
            if (!$this->checkRestrictions('activity-log-sys', 'activity-log-prj')) {
                return;
            }
            //$isRestricted = $this->checkRestrictions();

            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;

            $this->includeJs('js/disable-logging-exports.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;

                    //if (isRestricted && window.JHU_LoggingRestrictions?.disable) {
                    if (window.JHU_LoggingRestrictions?.disable) {
                        window.JHU_LoggingRestrictions.disable(tooltipText);
                    }
                });
            </script>
            <?php
            return;
        }
        // Block project XML export (metadata + data)
        if (PAGE == 'ProjectSetup/other_functionality.php')
        {
            if (!$this->checkRestrictions('project-xml-sys', 'project-xml-prj')) {
                return;
            }
            //$isRestricted = $this->checkRestrictions();
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$msgHtml = $this->getSystemSetting('export-page-msg') ?: $this->defaultBannerHtml;
            $msgHtml = $this->getMessageHtmlSetting();
            $this->includeJs('js/disable-project-xml-export.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;
                    const msgHtml = <?= json_encode($msgHtml) ?>;
                    //if (isRestricted && window.JHU_ProjectXmlRestrictions?.disable) {
                    if (window.JHU_ProjectXmlRestrictions?.disable) {
                        window.JHU_ProjectXmlRestrictions.disable(tooltipText, msgHtml);
                    }
                });
            </script>
            <?php
            return;
        }
        // Block Survey Distribution Tools exports
        if (PAGE == 'Surveys/invite_participants.php')
        {
            //echo 'survey-dist-export';
            $participant_list = $_GET['participant_list'] ?? null;
            if ($participant_list === '1') {
                if (!$this->checkRestrictions('survey-participant-list-sys', 'survey-participant-list-prj')) {
                    return;
                }
            }
            $export_log = $_GET['email_log'] ?? null;

            if ($export_log === '1') {
                if (!$this->checkRestrictions('survey-invite-log-sys', 'survey-invite-log-prj')) {
                    return;
                }
            }



            //$isRestricted = $this->checkRestrictions();
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$msgHtml = $this->getSystemSetting('export-page-msg') ?: $this->defaultBannerHtml;
            $msgHtml = $this->getMessageHtmlSetting();
            $this->includeJs('js/disable-survey-tools-exports.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;
                    const msgHtml = <?= json_encode($msgHtml) ?>;

                    //if (isRestricted && window.JHU_SurveyToolsRestrictions?.disable) {
                    if (window.JHU_SurveyToolsRestrictions?.disable) {
                        window.JHU_SurveyToolsRestrictions.disable(tooltipText, msgHtml);
                    }
                });
            </script>
            <?php
            return;
        }
        // Block Data Quality field comment log export button
        if (PAGE == 'DataQuality/field_comment_log.php')
        {
            //echo 'field-comment';
            if (!$this->checkRestrictions('field-comment-sys', 'field-comment-prj')) {
                return;
            }
            //$isRestricted = $this->checkRestrictions();
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$msgHtml = $this->getSystemSetting('export-page-msg') ?: $this->defaultBannerHtml;
            $msgHtml = $this->getMessageHtmlSetting();
            $this->includeJs('js/disable-field-comment-log-export.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;
                    const msgHtml = <?= json_encode($msgHtml) ?>;

                    //if (isRestricted && window.JHU_FieldCommentLogRestrictions?.disable) {
                    if (window.JHU_FieldCommentLogRestrictions?.disable) {
                        window.JHU_FieldCommentLogRestrictions.disable(tooltipText, msgHtml);
                    }
                });
            </script>
            <?php
            return;
        }
        // Block Data Quality resolution CSV export button
        if (PAGE == 'DataQuality/resolve.php')
        {
            //echo 'data-resolution';
            if (!$this->checkRestrictions('data-resolution-sys', 'data-resolution-prj')) {
                return;
            }
            //$isRestricted = $this->checkRestrictions();
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            $this->includeJs('js/disable-data-quality-exports.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;

                    //if (isRestricted && window.JHU_DataQualityRestrictions?.disable) {
                    if (window.JHU_DataQualityRestrictions?.disable) {
                        window.JHU_DataQualityRestrictions.disable(tooltipText);
                    }
                });
            </script>
            <?php
            return;
        }
        // Block Data Quality rule export links on Data Quality page
        if (PAGE == 'DataQuality/index.php')
        {
            //echo 'data-quality';
            if (!$this->checkRestrictions('data-quality-sys', 'data-quality-prj')) {
                return;
            }
            //$isRestricted = $this->checkRestrictions();
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$msgHtml = $this->getSystemSetting('export-page-msg') ?: $this->defaultBannerHtml;
            $msgHtml = $this->getMessageHtmlSetting();
            $this->includeJs('js/disable-data-quality-rule-exports.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;
                    const msgHtml = <?= json_encode($msgHtml) ?>;

                    //if (isRestricted && window.JHU_DataQualityRuleRestrictions?.disable) {
                    if (window.JHU_DataQualityRuleRestrictions?.disable) {
                        window.JHU_DataQualityRuleRestrictions.disable(tooltipText, msgHtml);
                    }
                });
            </script>
            <?php
            return;
        }
        // Block Mobile App file downloads on the Files tab
        if (PAGE == 'MobileApp/index.php' && isset($_GET['files']) && $_GET['files'] == '1')
        {
            //echo 'mobile-app';
            if (!$this->checkRestrictions('mobile-app-sys', 'mobile-app-prj')) {
                return;
            }
            //$isRestricted = $this->checkRestrictions();
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$msgHtml = $this->getSystemSetting('export-page-msg') ?: $this->defaultBannerHtml;
            $msgHtml = $this->getMessageHtmlSetting();
            $this->includeJs('js/disable-mobile-app-files-exports.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;
                    const msgHtml = <?= json_encode($msgHtml) ?>;

                    //if (isRestricted && window.JHU_MobileAppFilesRestrictions?.disable) {
                    if (window.JHU_MobileAppFilesRestrictions?.disable) {
                        window.JHU_MobileAppFilesRestrictions.disable(tooltipText, msgHtml);
                    }
                });
            </script>
            <?php
            return;
        }
        // Block Mobile App log downloads on the Logs tab
        if (PAGE == 'MobileApp/index.php' && isset($_GET['logs']) && $_GET['logs'] == '1')
        {
            //echo 'mobile-app';
            if (!$this->checkRestrictions('mobile-app-sys', 'mobile-app-prj')) {
                return;
            }
            //$isRestricted = $this->checkRestrictions();
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$msgHtml = $this->getSystemSetting('export-page-msg') ?: $this->defaultBannerHtml;
            $msgHtml = $this->getMessageHtmlSetting();
            $this->includeJs('js/disable-mobile-app-logs-exports.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;
                    const msgHtml = <?= json_encode($msgHtml) ?>;

                    //if (isRestricted && window.JHU_MobileAppLogsRestrictions?.disable) {
                    if (window.JHU_MobileAppLogsRestrictions?.disable) {
                        window.JHU_MobileAppLogsRestrictions.disable(tooltipText, msgHtml);
                    }
                });
            </script>
            <?php
            return;
        }

        // Block PDF dropdown options on instrument/survey pages
        $pdfPages = [
                //'DataEntry/index.php',
                'surveys/index.php',
                'Survey/index.php',
            'DataEntry/record_home.php'
        ];

        if (in_array(PAGE, $pdfPages, true))
        {
            //echo 'record-dashboard';
            if (!$this->checkRestrictions('pdf-export-sys', 'pdf-export-prj')) {
                return;
            }
            $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
            //$isRestricted = $this->checkRestrictions();

            $this->includeJs('js/pdf-drpdwn-restrictions.js');
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('css/SERcss.css') ?>">
            <script>
                $(function () {
                    //const isRestricted = <?= json_encode($isRestricted) ?>;
                    const tooltipText = <?= json_encode($tooltip) ?>;
                    //if (isRestricted && window.JHU_PdfRestrictions?.disable) {
                    if (window.JHU_PdfRestrictions?.disable) {
                        window.JHU_PdfRestrictions.disable(tooltipText);
                    }
                });
            </script>
            <?php
        }
    }

    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance)
    {
        //if ($this->isSuperUser()){
        //    $SuperuserBlockSetting = $this->getSystemSetting('allow-super-users');
        //    if($SuperuserBlockSetting){
        //        return;
        //    }
        //}
        //echo 'instrument-page';
        if (!$this->checkRestrictions('pdf-export-sys', 'pdf-export-prj')) {
            return;
        }
        //Hide PDF download options on Data Entry Page------------------------------------------------------------------------------------
        $tooltip = $this->getSystemSetting('export_tab_tooltip') ?: $this->defaultTooltip;
        //$isRestricted = $this->checkRestrictions();
        $this->includeJs('js/pdf-drpdwn-restrictions.js');
        $this->initializeJavascriptModuleObject();
        ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getUrl('css/SERcss.css'); ?>">
        <script>
            $(document).ready(function () {
                const module = <?=$this->getJavascriptModuleObjectName()?>;
                //const isRestricted = <?= json_encode($isRestricted) ?>;
                    //console.log("ISrestricted data entry",isRestricted);
                const tooltipText = <?= json_encode($tooltip) ?>;
                //if (isRestricted) {
                    window.JHU_PdfRestrictions.disable(tooltipText);
                //}

            });
        </script>
        <?php
        return;
        //---------------------------------------------------------------------------------------------------------------------------------
    }



    private function includeJs($path)
    {
        echo "<script src={$this->getUrl($path)}></script>";
    }

    public function redcap_module_ajax($action, $payload)
    {

        if ($action === 'collectClientInfo') {
            $result = $this->handleCollectClientInfo($payload);
            return [
                    'ok' => true,
                    'report' => $result['report'] ?? '',
                    'meta' => $result['meta'] ?? []
            ];
        }
        return false;
    }
    private function handleCollectClientInfo($payload)
    {
        // If coming from the module page, we might not have a server snapshot in session.
        // Build it now from the current request.
        $serverSnap = $_SESSION['my_em_server_snapshot'] ?? null;
        if (!is_array($serverSnap) || empty($serverSnap)) {
            $server = $_SERVER;
            $redcapUser = defined('USERID') ? USERID : ($_SESSION['username'] ?? null);

            $serverSnap = [
                    'timestamp' => date('c'),
                    'project_id' => (defined('PROJECT_ID') ? PROJECT_ID : null),
                    'redcap_user' => $redcapUser,

                // Common fields
                    'REMOTE_ADDR' => $server['REMOTE_ADDR'] ?? null,
                    'HTTP_X_FORWARDED_FOR' => $server['HTTP_X_FORWARDED_FOR'] ?? null,
                    'HTTP_X_REAL_IP' => $server['HTTP_X_REAL_IP'] ?? null,
                    'HTTP_USER_AGENT' => $server['HTTP_USER_AGENT'] ?? null,
                    'HTTP_ACCEPT_LANGUAGE' => $server['HTTP_ACCEPT_LANGUAGE'] ?? null,
                    'REQUEST_URI' => $server['REQUEST_URI'] ?? null,
                    'REQUEST_METHOD' => $server['REQUEST_METHOD'] ?? null,
                    'HTTP_REFERER' => $server['HTTP_REFERER'] ?? null,
            ];

            // Save for consistency/debugging
            $_SESSION['my_em_server_snapshot'] = $serverSnap;
        }

        $label = $payload['label'] ?? '';

        $clientInfoJson = $payload['clientInfoJson'] ?? '';
        $clientInfo = json_decode($clientInfoJson, true);
        if (!is_array($clientInfo)) {
            $clientInfo = ['(decode error)' => $clientInfoJson];
        }

        // Build a single readable report
        $lines = [];
        $lines[] = "=== REDCap Client Environment Report ===";
        if ($label !== '') {
            $lines[] = "Label: " . $label;
        }
        $lines[] = "Timestamp: " . ($serverSnap['timestamp'] ?? date('c'));
        $lines[] = "Project ID: " . ($serverSnap['project_id'] ?? '');
        $lines[] = "REDCap User: " . ($serverSnap['redcap_user'] ?? '');
        $lines[] = "";

        $lines[] = "=== SERVER (PHP \$_SERVER snapshot fields) ===";
        foreach ($serverSnap as $k => $v) {
            if (in_array($k, ['timestamp','project_id','redcap_user'], true)) continue;
            $lines[] = sprintf("%s = %s", $k, $this->stringifyForReport($v));
        }
        $lines[] = "";

        $lines[] = "=== CLIENT (Browser JS) ===";
        foreach ($clientInfo as $k => $v) {
            $lines[] = sprintf("%s = %s", $k, $this->stringifyForReport($v));
        }
        $lines[] = "";

        $lines[] = "=== ALL REQUEST HEADERS (best effort) ===";
        foreach ($this->getAllHeadersBestEffort() as $hk => $hv) {
            $lines[] = sprintf("%s: %s", $hk, $this->stringifyForReport($hv));
        }

        $body = implode("\n", $lines);

        // Email subject includes label if provided
        $subject = 'REDCap environment report (server + client)';
        if ($label !== '') {
            $subject .= " - " . $label;
        }

        \REDCap::email(
                'msherm12@jh.edu',
                'redcap@jh.edu',
                $subject,
                $body
        );

        // Return report for on-screen display
        return [
                'report' => $body,
                'meta' => [
                        'label' => $label,
                        'timestamp' => $serverSnap['timestamp'] ?? date('c'),
                        'user' => $serverSnap['redcap_user'] ?? '',
                        'project_id' => $serverSnap['project_id'] ?? ''
                ]
        ];
    }
    private function stringifyForReport($v): string
    {
        if ($v === null) return "(null)";
        if ($v === true) return "true";
        if ($v === false) return "false";
        if (is_scalar($v)) return (string)$v;

        // arrays/objects
        $json = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json === false ? "(unprintable)" : $json;
    }

    private function getAllHeadersBestEffort(): array
    {
        // getallheaders() may not exist depending on SAPIs
        if (function_exists('getallheaders')) {
            $h = getallheaders();
            return is_array($h) ? $h : [];
        }

        // Fallback: reconstruct from $_SERVER
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE','CONTENT_LENGTH'], true)) {
                $name = str_replace('_', '-', ucwords(strtolower($key), '_'));
                $headers[$name] = $value;
            }
        }
        ksort($headers);
        return $headers;
    }
    function parseIpList(string $input): array
    {
        $results = [];
        $entries = array_map('trim', explode(',', $input));

        foreach ($entries as $entry) {
            if ($entry === '') {
                continue;
            }

            if (strpos($entry, '-') !== false) {
                [$startIp, $endIp] = array_map('trim', explode('-', $entry, 2));

                if (
                        !filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
                        !filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                ) {
                    continue;
                }

                $start = ipv4ToUnsignedInt($startIp);
                $end   = ipv4ToUnsignedInt($endIp);

                if ($start > $end) {
                    [$start, $end] = [$end, $start];
                    [$startIp, $endIp] = [$endIp, $startIp];
                }

                $results[] = [
                        'type'  => 'range',
                        'start' => $start,
                        'end'   => $end,
                        'label' => $startIp . ' - ' . $endIp
                ];
            } else {
                if (filter_var($entry, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $value = ipv4ToUnsignedInt($entry);
                    $results[] = [
                            'type'  => 'single',
                            'start' => $value,
                            'end'   => $value,
                            'label' => $entry
                    ];
                }
            }
        }

        return $results;
    }
    private function parseIpListToArray(string $input): array
    {
        $results = [];

        $entries = array_map('trim', explode(',', $input));

        foreach ($entries as $entry) {
            if ($entry === '') {
                continue;
            }

            if (strpos($entry, '-') !== false) {
                [$startIp, $endIp] = array_map('trim', explode('-', $entry, 2));

                if (
                        !filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
                        !filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                ) {
                    continue;
                }

                $startLong = $this->ipv4ToUnsignedInt($startIp);
                $endLong   = $this->ipv4ToUnsignedInt($endIp);

                if ($startLong === false || $endLong === false) {
                    continue;
                }

                if ($startLong > $endLong) {
                    [$startLong, $endLong] = [$endLong, $startLong];
                }

                for ($i = $startLong; $i <= $endLong; $i++) {
                    $results[$this->unsignedIntToIpv4($i)] = true;
                }
            } else {
                if (filter_var($entry, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $results[$entry] = true;
                }
            }
        }

        return array_keys($results);
    }

    private function ipv4ToUnsignedInt(string $ip)
    {
        $packed = inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        $parts = unpack('N', $packed);
        return $parts[1];
    }

    private function unsignedIntToIpv4(int $int): string
    {
        return inet_ntop(pack('N', $int));
    }
    private function normalizeIpRangeInput(string $raw): string
    {
        // Convert all line endings to commas.
        $raw = str_replace(["\r\n", "\r", "\n"], ',', $raw);

        // Remove extra spaces around commas.
        $raw = preg_replace('/\s*,\s*/', ',', $raw);

        // Collapse repeated commas.
        $raw = preg_replace('/,+/', ',', $raw);

        // Trim leading/trailing commas and whitespace.
        return trim($raw, " \t\n\r\0\x0B,");
    }
    /**
     * Parses various IP range string formats into a structured array.
     *
     * Supported formats (can be mixed and comma-separated):
     *   - CIDR:               192.168.1.0/24
     *   - Range with dash:    192.168.1.10 - 192.168.1.200
     *                         10.5.30.40-10.5.30.80     (no spaces also ok)
     *   - Subnet mask:        172.16.0.0/255.255.0.0
     *   - Single IP:          8.8.8.8
     *   - Mixed:              192.168.1.1, 10.0.0.0/24, 172.16.5.10-172.16.5.50
     *
     * @param string $input Comma-separated or single IP/range string
     * @return array Array of associative arrays, each with:
     *               - 'start'    => string (IP)
     *               - 'end'      => string (IP)
     *               - 'type'     => 'cidr' | 'subnet_mask' | 'range' | 'single'
     *               - 'original' => the parsed substring
     *               Empty array on complete failure / invalid input
     */
    function parseIpRanges(string $input): array
    {
        $ranges = [];
        // Normalize line breaks into commas so note-box input can be entered
        // one item per line or comma-separated or mixed.
        $input = str_replace(["\r\n", "\r", "\n"], ',', $input);

        // Normalize spacing around commas.
        $input = preg_replace('/\s*,\s*/', ',', $input);

        // Collapse accidental duplicate commas.
        $input = preg_replace('/,+/', ',', $input);

        // Remove extra whitespace and commas from the ends.
        $input = trim($input, " \t\n\r\0\x0B,");

        if ($input === '') {
            return [];
        }

        // Split on commas (allow spaces around commas)
        $parts = array_map('trim', explode(',', $input));

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            // ────────────────────────────────────────────────
            // 1. CIDR notation: 192.168.1.0/24
            // ────────────────────────────────────────────────
            if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\/(\d{1,2})$/', $part, $m)) {
                [$_, $network, $prefix] = $m;
                $prefix = (int)$prefix;

                if ($prefix < 0 || $prefix > 32) {
                    continue;
                }

                $netLong = ip2long($network);
                if ($netLong === false) {
                    continue;
                }

                $mask = 0xFFFFFFFF << (32 - $prefix);
                $start = $netLong & $mask;
                $end   = $netLong | (~$mask & 0xFFFFFFFF);

                $ranges[] = [
                        'start'    => long2ip($start),
                        'end'      => long2ip($end),
                        'type'     => 'cidr',
                        'original' => $part,
                        'prefix'   => $prefix   // bonus field for CIDR
                ];
                continue;
            }

            // ────────────────────────────────────────────────
            // 2. Subnet mask notation: 192.168.0.0/255.255.255.0
            // ────────────────────────────────────────────────
            if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\/(\d{1,3}(?:\.\d{1,3}){3})$/', $part, $m)) {
                [$_, $network, $maskStr] = $m;
                $maskLong = ip2long($maskStr);
                if ($maskLong === false) {
                    continue;
                }

                $netLong = ip2long($network);
                if ($netLong === false) {
                    continue;
                }

                $start = $netLong & $maskLong;
                $end   = $netLong | (~$maskLong & 0xFFFFFFFF);

                $ranges[] = [
                        'start'    => long2ip($start),
                        'end'      => long2ip($end),
                        'type'     => 'subnet_mask',
                        'original' => $part
                ];
                continue;
            }

            // ────────────────────────────────────────────────
            // 3. Explicit range: 192.168.1.50 - 192.168.1.200
            //    Also accepts 10.0.0.5-10.0.0.55 (no spaces)
            // ────────────────────────────────────────────────
            if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\s*[-–—]\s*(\d{1,3}(?:\.\d{1,3}){3})$/', $part, $m)) {
                [$_, $startIp, $endIp] = $m;

                $startLong = ip2long($startIp);
                $endLong   = ip2long($endIp);

                if ($startLong === false || $endLong === false) {
                    continue;
                }

                // Normalize order
                if ($startLong > $endLong) {
                    [$startIp, $endIp] = [$endIp, $startIp];
                    [$startLong, $endLong] = [$endLong, $startLong];
                }

                $ranges[] = [
                        'start'    => $startIp,
                        'end'      => $endIp,
                        'type'     => 'range',
                        'original' => $part
                ];
                continue;
            }

            // ────────────────────────────────────────────────
            // 4. Single valid IP → treat as tiny range
            // ────────────────────────────────────────────────
            if (filter_var($part, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ranges[] = [
                        'start'    => $part,
                        'end'      => $part,
                        'type'     => 'single',
                        'original' => $part
                ];
                continue;
            }

            // If we get here → invalid part, silently skip
        }

        return $ranges;
    }
    function checkRestrictions($sysetting,$prjsetting): bool //returns true if blocked
    {
        if ($this->isSuperUser()){
            $SuperuserBlockSetting = $this->getSystemSetting('allow-super-users');
            if($SuperuserBlockSetting){
                return false;
            }
        }
        if ($this->checkProjectTypeAgainstList()) { //Check against project type list
            return false;
        }
        if (!$this->settingCheck($sysetting, $prjsetting)) { //check project override
            return false;
        }
        if ($this->checkAgainstUserIDList()) { //Check UserID list
            return false;
        }
        if ($this->checkAgainstUserDAGList()) { //Check DAG list
            return false;
        }
        $acgcheck = $this->getSystemSetting('acg-id-exclusion');

        $acgList = array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', (string) $acgcheck))
        );

        if (!empty($acgList) && $this->checkAccessControlGroup($acgList)) {
            return false;
        }

//        $projectOverrideUserIDCheck = $this->checkAgainstUserIDList();  //Check UserID list
//        $projectOverrideUserDAGCheck = $this->checkAgainstUserDAGList();    //check DAG list
//        $isProjectTypeAllowed = $this->checkProjectTypeAgainstList();
//
//        if ($projectOverrideUserIDCheck || $projectOverrideUserDAGCheck || $isProjectTypeAllowed) {  //If any are true, then override the block
//            return false;
//        }

        //grab and parse system IP ranges
        $systemIpRangeRaw = trim((string) $this->getSystemSetting('ip-range-raw'));
        $systemIpRanges = $this->parseIpRanges($systemIpRangeRaw);
        //grb project IP address overrides
        $projectIpRangeOverrideRaw = trim((string) $this->getProjectSetting('ip-range-override'));
        $projectIpRanges = [];
        //parse if not empty
        if ($projectIpRangeOverrideRaw !== '') {
            $projectIpRanges = $this->parseIpRanges($projectIpRangeOverrideRaw);
        }
        //combine the two existing arrays
        $combinedIpRanges = array_merge($systemIpRanges, $projectIpRanges);

        $ipAddress = $this->getIPAddress(); //grab current IP address
        $isRestricted = !$this->ipInAnyRange($ipAddress, $combinedIpRanges); //check if in array
        return $isRestricted;
    }
    public function checkAccessControlGroup(array $acgList): bool
    {
        if (\REDCap::versionCompare(REDCAP_VERSION, '16.0.0', '<')) {
            return false;
        }

        if (!$this->isACGEnabled()) {
            return false;
        }

        return $this->getUserACG($acgList);
    }
    public function isACGEnabled() //returns true if ACG is system enabled
    {
        try {
            $result = $this->query(
                    'SELECT value FROM redcap_config WHERE field_name = ?',
                    ['acg_enabled_global']
            );

            if ($result && $row = $result->fetch_assoc()) {

                return (int)$row['value'] === 1;
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }
    public function getUserACG(array $acgList): bool
    {
        try {
            if (empty($acgList)) {
                return false;
            }

            $userInfo = \User::getUserInfo(USERID);
            $uiid = $userInfo['ui_id'] ?? null;

            if (empty($uiid)) {
                return false;
            }

            $placeholders = implode(',', array_fill(0, count($acgList), '?'));

            $result = $this->query(
                    "SELECT 1
             FROM redcap_access_control_groups_users
             WHERE user_id = ?
               AND group_id IN ($placeholders)
             LIMIT 1",
                    array_merge([$uiid], array_values($acgList))
            );

            return $result && $result->num_rows > 0;

        } catch (\Throwable $e) {
            return false;
        }
    }


    public function checkProjectTypeAgainstList(): bool
    {
        $project = new \Project($this->getProjectId());
        $purpose = (string) ($project->project['purpose'] ?? '');

        $map = [
                "2" => 'proj-type-research-sys',
                "3" => 'proj-type-qi-sys',
                "4" => 'proj-type-op-support-sys',
                "0" => 'proj-type-practice-sys',
                "1" => 'proj-type-other-sys',
                "88" => 'proj-type-none-sys',
        ];

        $settings = [
                (string) $this->getSystemSetting('proj-type-research-sys'),
                (string) $this->getSystemSetting('proj-type-qi-sys'),
                (string) $this->getSystemSetting('proj-type-op-support-sys'),
                (string) $this->getSystemSetting('proj-type-practice-sys'),
                (string) $this->getSystemSetting('proj-type-other-sys'),
                (string) $this->getSystemSetting('proj-type-none-sys')
        ];
        // If none are checked, allow.
        if (!in_array("1", $settings, true)) {
            return true;
        }

        // Unknown purpose block
        if (!isset($map[$purpose])) {
            $allow = (string) $this->getSystemSetting('proj-type-none-sys');
            if($allow === '1') {
                return false;
            }else{
                return true;
            }
        }

        $allow = (string) $this->getSystemSetting($map[$purpose]);
        if($allow === '1') {
            return false;
        }else{
            return true;
        }
    }

    public function checkAgainstUserIDList(): bool // returns true if username found in list
    {
        // Get the project setting value.
        $userIDlist = (string) $this->getProjectSetting('user-id-exclusion');

        // Allow users to enter values either comma-separated, one-per-line, or mixed.
        $userIDlist = str_replace(["\r\n", "\r", "\n"], ',', $userIDlist);
        $userIDlist = preg_replace('/\s*,\s*/', ',', $userIDlist);
        $userIDlist = preg_replace('/,+/', ',', $userIDlist);
        $userIDlist = trim($userIDlist, " \t\n\r\0\x0B,");

        // If the setting is blank or not set, false.
        if ($userIDlist === '') {
            return false;
        }

        // Split the normalized list into an array and trim each value.
        $excludedUsers = array_map('trim', explode(',', $userIDlist));

        // Remove empty values in case the list still contains blanks.
        $excludedUsers = array_filter($excludedUsers, function ($value) {
            return $value !== '';
        });

        // Check whether the current REDCap user ID matches one of the user IDs in the exclusion list.
        return in_array((string) USERID, $excludedUsers, true);
    }
    public function checkAgainstUserDAGList(): bool // returns true if user's DAG found in list
    {
        // Get the project setting value.
        $userDAGList = $this->getProjectSetting('dag-id-exclusion');

        // Allow users to enter values either comma-separated, one-per-line, or mixed.
        $userDAGList = str_replace(["\r\n", "\r", "\n"], ',', $userDAGList);
        $userDAGList = preg_replace('/\s*,\s*/', ',', $userDAGList);
        $userDAGList = preg_replace('/,+/', ',', $userDAGList);
        $userDAGList = trim($userDAGList, " \t\n\r\0\x0B,");

        // If the setting is blank or not set, false.
        if ($userDAGList === '') {
            return false;
        }

        // Split the normalized list into an array and trim each value.
        $excludedDAGs = array_map('trim', explode(',', $userDAGList));

        // Remove empty values in case the list still contains blanks.
        $excludedDAGs = array_filter($excludedDAGs, function ($value) {
            return $value !== '';
        });

        // Get the current user's DAG name/code.
        $rights = REDCap::getUserRights(USERID);
        // Check if the user is in a data access group (DAG)
        $group_id = $rights[USERID]['group_id'];
        if ($group_id == '') {// If $group_id is blank, then user is not in a DAG
            return false;
        } //else {//possibly useful if we need the name of the group instead of id
        //$dagID = REDCap::getGroupNames(false, $group_id);
        //$dagName = REDCap::getGroupNames(true, $group_id);
        //}
        // Return true if the current user's DAG is in the exclusion list.
        return in_array((string) $group_id, $excludedDAGs, true);
    }

    public function getIPAddress(){
        $realIP = \System::clientIpAddress(); //use REDCap's built-in function to get client IP address
        return $realIP;
    }
    function ipInAnyRange(string $ip, array $parsedRanges): bool
    {
        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return false;
        }

        foreach ($parsedRanges as $range) {
            $startLong = ip2long($range['start']);
            $endLong   = ip2long($range['end']);
            if ($ipLong >= $startLong && $ipLong <= $endLong) {
                return true;
            }
        }
        return false;
    }
    private function ipIsInList(string $ip, array $parsedList): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        return in_array($ip, $parsedList, true);
    }
    public function handleSendItUpload($tooltip,$msgHtml): void
    {
        ?>
        <script>
            $(function () {
                const tooltip = <?= json_encode($tooltip) ?>;
                const sendItSelector = 'a[onclick^="popupSendIt("]';
                const downloadSelector = 'a[href*="route=FileRepositoryController:download"]';
                const msgHtml = <?= json_encode($msgHtml) ?>;

                function makeDeadBanIcon() {
                    return $('<span>')
                        .addClass('jhu-disabled-export-icon')
                        .attr({
                                  title: tooltip,
                                  'aria-disabled': 'true'
                              })
                        .css({
                                 cursor: 'not-allowed',
                                 display: 'inline-block',
                                 verticalAlign: 'middle'
                             })
                        .html('<i class="fa-solid fa-ban jhu-disabled-export-icon"></i>');
                }

                function replaceSendItLinks() {
                    $(sendItSelector).each(function () {
                        const $link = $(this);

                        if ($link.data('jhuReplaced')) {
                            return;
                        }

                        const $dead = $('<span>')
                            .addClass('jhu-disabled-sendit')
                            .attr({
                                      title: tooltip,
                                      'aria-disabled': 'true'
                                  })
                            .css({
                                     cursor: 'not-allowed',
                                     fontSize: '10px',
                                     color: '#777',
                                     textDecoration: 'none',
                                     display: 'inline-block'
                                 })
                            .html('<i class="fa-solid fa-ban" style="color:#C00000;margin-right:4px;"></i>Send data file');

                        $link.data('jhuReplaced', true);
                        $link.replaceWith($dead);
                    });
                }

                function injectExportBanners(messageHtml) {
                    if (!messageHtml || messageHtml.trim() === '') {
                        return;
                    }

                    $('.simpleDialog').each(function () {
                        const $dialog = $(this);

                        if ($dialog.data('jhuBannerInjected')) {
                            return;
                        }

                        const $target = $dialog.find('div[style*="background-color:#F0F0F0"]').first();

                        if (!$target.length) {
                            return;
                        }

                        const $banner = $('<div>')
                            .addClass('jhu-restriction-banner jhu-export-dialog-banner')
                            .html(messageHtml);

                        $banner.insertBefore($target);
                        $dialog.data('jhuBannerInjected', true);
                    });
                }

                function replaceDownloadLinks() {
                    $(downloadSelector).each(function () {
                        const $link = $(this);

                        if ($link.data('jhuReplaced')) {
                            return;
                        }

                        const $dead = makeDeadBanIcon();

                        $link.data('jhuReplaced', true);
                        $link.replaceWith($dead);
                    });
                }

                function applyRestrictions() {
                    replaceSendItLinks();
                    replaceDownloadLinks();
                    injectExportBanners(msgHtml);
                }

                applyRestrictions();

                const observer = new MutationObserver(function () {
                    applyRestrictions();
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });

                $(document)
                    .off('click.jhuSendItBlock', sendItSelector)
                    .on('click.jhuSendItBlock', sendItSelector, function (e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        return false;
                    });

                $(document)
                    .off('click.jhuDownloadBlock', downloadSelector)
                    .on('click.jhuDownloadBlock', downloadSelector, function (e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        return false;
                    });
            });
        </script>
        <?php
    }

}

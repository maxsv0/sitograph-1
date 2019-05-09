<?php
// load default handler to process common functions
$handlerPath = ABS_MODULE."/sitograph/module-table.php";
if (file_exists($handlerPath)) {
    include($handlerPath);
} else {
    msv_message_error("Module handler file not found <b>$handlerPath</b>");
}
// USERS custom functions
if (!empty($_REQUEST["send_to_crm"])) {
    $resultCheck = db_get(TABLE_FUNNEL_LEADS,' `id` = '.$_REQUEST["send_to_crm"]);
    if (count($resultCheck["data"]) === 0) {
        msv_message_error($resultCheck["msg"]);
    } else {
        
        $lead_data = $resultCheck['data'];

        $result = api_funnels_crm_lead_send($lead_data);
        if ($result['ok']) {
            msv_message_ok('ok');
            
            var_dump($result['data']);
            
            $rowUpd = $result['data'];
            $rowUpd['id'] = $lead_data['id'];
            $rowUpd['status'] = 1;
            $rowUpd['status_info'] = $result['ok'];

            $resultUpdateLead = db_update_row(TABLE_FUNNEL_LEADS, $rowUpd);
            //var_dump($resultUpdateLead);
        } else {
            msv_message_error($result["msg"]);
        }
    }
}
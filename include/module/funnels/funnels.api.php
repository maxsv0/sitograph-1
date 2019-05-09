<?php

/**
 * API extension for module blog
 *
 * Allow URLs like:
 * 		/api/funnels/add/				=> access: accessAPIAdd
 *
 * @param object $module Current module object
 * @return string JSON encoded string containing API call result
 */
function api_request_funnels($module) {
    $request = msv_get('website.requestUrlMatch');
    $apiName = $request[1];
    $apiType = $request[2];

    switch ($apiType) {
        case "add":
            if (!msv_check_accessuser($module->accessAPIAdd)) {
                $resultQuery = array(
                    "ok" => false,
                    "data" => array(),
                    "msg" => _t("msg.api.no_access"),
                );
            } else {
                $item = msv_process_tabledata(TABLE_FUNNEL_LEADS, "");
                $resultQuery = api_funnels_lead_add($item, array("EmailNotifyAdmin"));
            }
            break;
        case "send_crm":
            if (!msv_check_accessuser($module->accessAPISendCRM)) {
                $resultQuery = array(
                    "ok" => false,
                    "data" => array(),
                    "msg" => _t("msg.api.no_access"),
                );
            } else {
                $resultQuery = api_funnels_crm_lead_send_all();
            }
            break;
        default:
            $resultQuery = array(
                "ok" => false,
                "data" => array(),
                "msg" => _t("msg.api.wrong_api"),
            );
            break;
    }

    // do not output sql for security reasons
    unset($resultQuery["sql"]);

    // output result as JSON
    return json_encode($resultQuery);
}

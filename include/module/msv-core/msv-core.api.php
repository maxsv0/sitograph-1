<?php

/**
 * Website Core API calls
 *
 * Allow URLs like:
 *              /api/core/status/                       => access required: accessAPIStatus
 *              /api/core/update-all/                   => access required: accessAPIUpdateAll
 *
 *
 * @return string JSON encoded string containing API call result
 */
function api_request_core($module) {
    $request = msv_get('website.requestUrlMatch');
    $apiType = $request[2];

    // get jobs depending on request
    switch ($apiType) {
        case "status":
            if (!msv_check_accessuser($module->accessAPIStatus)) {
                $resultQuery = array(
                    "ok" => false,
                    "data" => array(),
                    "msg" => _t("msg.api.no_access"),
                );

            } else {

                $websiteFunctions = array();
                $list = get_defined_functions();
                foreach($list["user"] as $fnName)  {
                    $prefix = substr($fnName, 0, strpos($fnName, "_"));
                    if ($prefix === "msv" || $prefix === "db" || $prefix === "api" || $prefix === "install") {
                        $websiteFunctions[$prefix][] = $fnName;
                    }
                }
                sort($websiteFunctions["msv"]);
                sort($websiteFunctions["db"]);
                sort($websiteFunctions["api"]);
                sort($websiteFunctions["install"]);

                // TODO: add data here
                $resultQuery = array(
                    "ok" => true,
                    "data" => array(
                        "php" => $websiteFunctions,
                    ),
                    "msg" => "Success",
                );
            }
            break;
        case "update-all":
            if (!msv_check_accessuser($module->accessAPIUpdateAll)) {
                $resultQuery = array(
                    "ok" => false,
                    "data" => array(),
                    "msg" => _t("msg.api.no_access"),
                );

            } else {
                $r = msv_update_allmodules();

                $resultQuery = array(
                    "ok" => true,
                    "data" => array(),
                    "msg" => $r ? "Successfully updated" : "Something went wrong",
                );
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

    // output result as JSON
    return json_encode($resultQuery);
}


/**
 * Allows to run cron jobs
 * All calls require admin level access
 *
 * Allow URLs like:
 *              /api/cron/weekly/
 *              /api/cron/daily/
 *              /api/cron/hourly/
 *
 * @return string JSON encoded string containing API call result
 */
function api_request_cron($module) {
    if (!msv_check_accessuser("admin")) {
        $resultQuery = array(
            "ok" => false,
            "data" => array(),
            "msg" => _t("msg.api.no_access"),
        );
        return json_encode($resultQuery);
    }

    $request = msv_get('website.requestUrlMatch');
    $apiType = $request[2];

    $tm_start = time();
    $item = array(
        "published" => 1,
        "job_id" => 0,
        "job_name" => "cron ".$apiType,
        "time_start" => $tm_start,
        "result_ok" => 0,
        "result_msg" => "started",
    );
    $resultLog = db_add(TABLE_CRONJOBS_LOGS, $item);
    $logID = $resultLog["insert_id"];
    $jobIndex = 0;

    // get jobs depending on request
    switch ($apiType) {
        case "weekly":
        case "daily":
        case "hourly":
        case "minutely":
            $resultQuery = db_get_list(TABLE_CRONJOBS, " `status` = 'active' and `type` = '".$apiType."'", "`id` desc", 999, "");
            break;
        default:
            $resultQuery = array(
                "ok" => false,
                "data" => array(),
                "msg" => _t("msg.api.wrong_api"),
            );
            break;
    }

    // run selected jobs
    if (!empty($resultQuery["data"])) {
        $resultCron = array();
        foreach($resultQuery["data"] as $rowJob) {
            $jobIndex++;
            $code = $rowJob["code"];
            $result = array(
                "ok" => false,
                "msg" => "unknown",
            );
            $tm_start = time();

            if (!empty($rowJob["url_local"])) {
                $url = HOME_LINK.$rowJob["url_local"]."&ajaxcall=1";
                $cont = file_get_contents($url);
                $result = json_decode($cont, true);
            } else {
                eval($code);
            }

            $tm_end = time();

            $item = array(
                "published" => 1,
                "job_id" => $rowJob["id"],
                "job_name" => $rowJob["name"],
                "time_start" => $tm_start,
                "time_end" => $tm_end,
                "result_ok" => @(string)$result["ok"],
                "result_msg" => @(string)$result["msg"],
            );

            $resultRun = db_add(TABLE_CRONJOBS_LOGS, $item);
            db_update(TABLE_CRONJOBS, "last_run", "NOW()", "`id` = ".$rowJob["id"]);
            db_update(TABLE_CRONJOBS, "last_result", "'".(int)$resultRun["ok"]."'", "`id` = ".$rowJob["id"]);

            $rowJob["run_result"] = $result["msg"];

            $resultCron[] = $rowJob;
        }

        $resultQuery["data"] = $resultCron;
    }

    $tm_end = time();
    db_update(TABLE_CRONJOBS_LOGS, "result_ok", $jobIndex, "`id` = ".$logID);
    db_update(TABLE_CRONJOBS_LOGS, "time_end", $tm_end, "`id` = ".$logID);
    db_update(TABLE_CRONJOBS_LOGS, "result_msg", "'finished'", "`id` = ".$logID);

    // do not output sql for security reasons
    unset($resultQuery["sql"]);

    // output result as JSON
    return json_encode($resultQuery);
}

/**
 * API extension for module msv-core
 * Allows to manage table TABLE_SETTINGS
 * All calls require admin level access

 * Allow URLs like:
 *              /api/settings/list/
 *              /api/settings/add/
 *              /api/settings/edit/
 *
 * @return string JSON encoded string containing API call result
 */
function api_request_settings($module) {
    if (!msv_check_accessuser("admin")) {
        $resultQuery = array(
            "ok" => false,
            "data" => array(),
            "msg" => _t("msg.api.no_access"),
        );
        return json_encode($resultQuery);
    }

    $request = msv_get('website.requestUrlMatch');
    $apiType = $request[2];

    switch ($apiType) {
        case "list":
            $resultQuery = db_get_list(TABLE_SETTINGS, "", "`id` desc", 999, "");
            break;
        case "add":
            $item = msv_process_tabledata(TABLE_SETTINGS, "");
            $resultQuery = db_add(TABLE_SETTINGS, $item);
            break;
        case "edit":
            if (empty($_REQUEST["updateName"]) || empty($_REQUEST["updateID"]) || !isset($_REQUEST["updateValue"]) ) {
                $resultQuery = array(
                    "ok" => false,
                    "data" => array(),
                    "msg" => _t("msg.api.wrong_api"),
                );
            } else {
                $resultQuery = db_update(TABLE_SETTINGS, $_REQUEST["updateName"], "'".db_escape($_REQUEST["updateValue"])."'", "`id` = ".(int)$_REQUEST["updateID"]);
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

/**
 * API extension for module msv-core
 * Allows to manage table TABLE_STRUCTURE
 * All calls require admin level access

 * Allow URLs like:
 *              /api/structure/list/
 *              /api/structure/add/
 *              /api/structure/edit/
 *
 * @return string JSON encoded string containing API call result
 */
function api_request_structure($module) {
    if (!msv_check_accessuser("admin")) {
        $resultQuery = array(
            "ok" => false,
            "data" => array(),
            "msg" => _t("msg.api.no_access"),
        );
        return json_encode($resultQuery);
    }

    $request = msv_get('website.requestUrlMatch');
    $apiType = $request[2];

    switch ($apiType) {
        case "list":
            $resultQuery = db_get_list(TABLE_STRUCTURE, "", "`id` desc", 999, "");
            break;
        case "add":
            $item = msv_process_tabledata(TABLE_STRUCTURE, "");
            $resultQuery = msv_add_structure($item);
            break;
        case "edit":
            if (empty($_REQUEST["updateName"]) || empty($_REQUEST["updateID"]) || !isset($_REQUEST["updateValue"]) ) {
                $resultQuery = array(
                    "ok" => false,
                    "data" => array(),
                    "msg" => _t("msg.api.wrong_api"),
                );
            } else {
                $resultQuery = db_update(TABLE_STRUCTURE, $_REQUEST["updateName"], "'".db_escape($_REQUEST["updateValue"])."'", "`id` = ".(int)$_REQUEST["updateID"]);
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

/**
 * API extension for module msv-core
 * Allows to manage table TABLE_DOCUMENTS
 * All calls require admin level access

 * Allow URLs like:
 *              /api/document/list/
 *              /api/document/add/
 *              /api/document/edit/
 *
 * @return string JSON encoded string containing API call result
 */
function api_request_document($module) {
    if (!msv_check_accessuser("admin")) {
        $resultQuery = array(
            "ok" => false,
            "data" => array(),
            "msg" => _t("msg.api.no_access"),
        );
        return json_encode($resultQuery);
    }

    $request = msv_get('website.requestUrlMatch');
    $apiType = $request[2];

    switch ($apiType) {
        case "list":
            $resultQuery = db_get_list(TABLE_DOCUMENTS, "", "`id` desc", 999, "");
            break;
        case "add":
            $item = msv_process_tabledata(TABLE_DOCUMENTS, "");
            $resultQuery = msv_add_document($item);
            break;
        case "edit":
            if (empty($_REQUEST["updateName"]) || empty($_REQUEST["updateID"]) || !isset($_REQUEST["updateValue"]) ) {
                $resultQuery = array(
                    "ok" => false,
                    "data" => array(),
                    "msg" => _t("msg.api.wrong_api"),
                );
            } else {
                $resultQuery = db_update(TABLE_DOCUMENTS, $_REQUEST["updateName"], "'".db_escape($_REQUEST["updateValue"])."'", "`id` = ".(int)$_REQUEST["updateID"]);
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

/**
 * API extension for module msv-core to upload picture to File Storage
 * Requires admin level access

 * URL: /api/uploadpic/
 *
 * @return string Path of a stored file in case of success, error code otherwise.
 */
function api_upload_picture($module) {
    if (!msv_check_accessuser("admin")) {
        return _t("msg.api.no_access");
    }

    $allowedTypes = array(
        IMAGETYPE_PNG => "png",
        IMAGETYPE_JPEG => "jpg",
        IMAGETYPE_GIF => "gif"
    );

    if (!empty($_FILES["uploadFile"])) {
        if (empty($_FILES["uploadFile"]['tmp_name'])) {
            return _t("msg.api.upload_not_found");
        }

        $detectedType = exif_imagetype($_FILES["uploadFile"]['tmp_name']);
        if (empty($detectedType)) {
            return _t("msg.api.failed_detect_image_type");
        }

        if (array_key_exists($detectedType, $allowedTypes)) {
            $fileType = $allowedTypes[$detectedType];

            $table = $_REQUEST["table"];
            $field = $_REQUEST["field"];
            $itemID = $_REQUEST["itemID"];

            // TODO:
            // check $table and $field (config ..)

            // extract file information
            $file = $_FILES["uploadFile"];
            $fileName = $file["name"];
            if (!empty($itemID)) {
                $fileName = $itemID."-".$fileName;
            }

            // store Picture
            $fileResult = msv_store_pic($file["tmp_name"], $fileType, $fileName, $table, $field);
            if (!is_numeric($fileResult)) {
                return CONTENT_URL."/".$fileResult;
            } else {
                return $fileResult;
            }
        } else {
            return _t("msg.api.type_error_allowed")." ".implode(", ", $allowedTypes);
        }
    }
}

/**
 * API extension for module msv-core to upload file to File Storage
 * Requires admin level access

 * URL: /api/uploadpic/
 *
 * @return string Path of a stored file in case of success, error code otherwise.
 */
function api_upload_file($module) {
    if (!msv_check_accessuser("admin")) {
        return _t("msg.api.no_access");
    }

    if (!empty($_FILES["uploadFile"])) {
        if (empty($_FILES["uploadFile"]['tmp_name'])) {
            return _t("msg.api.upload_not_found");
        }

        $typeExt = msv_format_mimetype($_FILES["uploadFile"]['type']);

        if (empty($typeExt)) {
            return _t("msg.api.failed_detect_file_type");
        }

        $table = $_REQUEST["table"];
        $field = $_REQUEST["field"];
        $itemID = $_REQUEST["itemID"];

        // TODO:
        // check $table and $field (config ..)

        // extract file information
        $file = $_FILES["uploadFile"];
        $fileName = $file["name"];
        if (!empty($itemID)) {
            $fileName = $itemID."-".$fileName;
        }

        // store Picture
        $fileResult = msv_store_pic($file["tmp_name"], $typeExt, $fileName, $table, $field);
        if (!is_numeric($fileResult)) {
            return CONTENT_URL."/".$fileResult;
        } else {
            return $fileResult;
        }
    }
}



/**
 * API procedure to create URL-like string
 * Require admin level access
 *
 * @return string JSON encoded string containing API call result
 */
function api_format_url($module) {
    if (!msv_check_accessuser("admin")) {
        $resultQuery = array(
            "ok" => false,
            "data" => array(),
            "msg" => _t("msg.api.no_access"),
        );
        return json_encode($resultQuery);
    }

    $resultQuery = array(
        "ok" => false,
        "data" => array(),
        "msg" => "Invalid input",
    );

    if (empty($_REQUEST['str'])) {
        return json_encode($resultQuery);
    }

    // set URL prefix in case if $_REQUEST['index'] or $_REQUEST['table'] is present
    $index = '';
    if (!empty($_REQUEST['index'])) {
        $index = $_REQUEST['index'];
    } else {
        if (!empty($_REQUEST['table'])) {
            $index = db_get_autoincrement($_REQUEST['table']);
        }
    }

    $str = msv_format_url(msv_truncate_text(((!empty($index)? $index.'-':'').$_REQUEST['str']),150));
    if (!empty($str)) {
        $resultQuery = array(
            "ok" => true,
            "data" => $str,
            "msg" => "Success",
        );
    }

    return json_encode($resultQuery);
}

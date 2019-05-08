<?php

/**
 * Register new leads
 * Database table: TABLE_FUNNELS_LEADS
 *
 * checks for required fields and correct values
 * $row["email"] is required, has to be valid email and not used
 * unverified emails will receive link to confirm email
 * message will be generated in case if email was sent
 *
 * @param array $row Associative array with data to be inserted
 * @param array $options Optional list of flags. Supported: EmailNotifyUser, EmailNotifyAdmin
 * @return array Result of a API call
 */
function api_funnels_lead_add($row, $options = array()) {
    $result = array(
        "ok" => false,
        "data" => array(),
        "msg" => "",
    );



    if (empty($row["member_email"])) {
        $result["msg"] = _t("msg.users.noemail");
        return $result;
    } elseif (!msv_check_email($row["member_email"])) {
        $result["msg"] = _t("msg.wrong_email");
        return $result;
    }

    if (empty($row["funnel_forms_id"])) {
        $result["msg"] = _t("msg.funnels.funnel_forms_id_exists");
        return $result;
    } else {
        $row["funnel_forms_id"] = (int)$row["funnel_forms_id"];
    }

    if (!empty($row["utm"])) {
        $utm_arr = json_decode($row["utm"], true);

        $row["utm_content"] = $utm_arr["utm_content"];
        $row["utm_medium"] = $utm_arr["utm_medium"];
        $row["utm_source"] = $utm_arr["utm_source"];
        $row["utm_term"] = $utm_arr["utm_term"];
        $row["utm_campaign"] = $utm_arr["utm_campaign"];
    }

    $resultCheck = db_get(
        TABLE_FUNNELS_LEADS,
        " `member_email` = '".db_escape($row["member_email"]).
        "' AND  `funnel_forms_id` = ".$row["funnel_forms_id"]
    );
    if ($resultCheck["ok"] && !empty($resultCheck["data"])) {
        $result["msg"] = _t("msg.users.email_exists");
        return $result;
    }

    if (empty($row["published"])) {
        $row["published"] = 1;
    } else {
        $row["published"] = (int)$row["published"];
    }

    // assign user to each language (*)
    $result = db_add(TABLE_FUNNELS_LEADS, $row, "*");

    if ($result["ok"]) {
        $result["msg"] = _t("msg.funnels.funnel_leads_saved");
    }

    return $result;
}
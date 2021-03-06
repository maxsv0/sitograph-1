<ul class="nav nav-pills">
    <li class="active"><a href="{$lang_url}{$admin_url}?section=leads">Last 24h</a></li>
    <li><a href="{$lang_url}{$admin_url}?section=leads&range=7d">Last 7 days</a></li>
    <li><a href="{$lang_url}{$admin_url}?section=leads&range=30d">Last 30 days</a></li>
</ul>
<br>

<table class="table">
    <tr>
        <td>ID</td>
        <td>Status</td>
        <td>Date</td>
        <td>User</td>
        <td>Device</td>
        <td>IP</td>
{if $service_ip_info}
        <td>IP info</td>
{/if}
        <td>UA</td>
{if $service_ua_info}
        <td>UA info</td>
{/if}
    </tr>

    {foreach from=$lead_list item=lead name=loop}

        <tr>
            <td class="col-sm-1">{$lead.id}</td>
            <td class="col-sm-1">
                {if $lead.status == "online"}
                    <span class="label label-success">online</span>
                {else}
                    <span class="label label-default">offline</span>
                {/if}
            </td>
            <td class="col-sm-2">{$lead.last_action}</td>
            <td class="col-sm-1">
{if $lead.user_id}
    <p>ID: {$lead.user_id}</p>
    <p><a href="{$lang_url}{$admin_url}?section=users&table=users&edit={$lead.user_id}">edit user</a></p>
{else}
           <i>anonymous</i>
{/if}
            </td>
            <td class="col-sm-1">{$lead.device_type}</td>
            <td class="col-sm-1">
                <p>
                    {$lead.ip}
                </p>
            </td>
{if $service_ip_info}
            <td class="col-sm-2">
                <a href="{$lang_url}/api/lead/loadip/{$lead.id}/" class="pull-right btn btn-default btn-sm" onclick="load_ajax(this, $('#ipinfo{$lead.id}'));return false;"><span class="glyphicon glyphicon-refresh"></span></a>

                <div class="infowell" id="ipinfo{$lead.id}">

{if $lead.ip_info}
        {include "$themePath/sitograph/seo/lead_ipinfo.tpl" info=$lead.ip_info}
{else}
    <i>empty</i>
{/if}
                </div>
            </td>
{/if}
            <td class="col-sm-1">
                <p class="small" style="max-width:100px; overflow: auto; white-space: nowrap;">
                    {$lead.ua}
                </p>
            </td>
{if $service_ua_info}
            <td class="col-sm-3">
                <a href="{$lang_url}/api/lead/loadua/{$lead.id}/" class="pull-right btn btn-default btn-sm"  onclick="load_ajax(this, $('#uainfo{$lead.id}'));return false;"><span class="glyphicon glyphicon-refresh"></span></a>

                <div class="infowell" id="uainfo{$lead.id}">
{if $lead.ua_info}
        {include "$themePath/sitograph/seo/lead_uainfo.tpl" info=$lead.ua_info}
{else}
        <i>empty</i>
{/if}
                </div>
            </td>
{/if}
        </tr>

    {/foreach}

</table>

{if !$service_ip_info}
<div class="alert alert-warning">
    <div class="row">
        <div class="col-xs-1"><img src="{CONTENT_URL}/{$msv_seo.preview}" class="img-thumbnail"></div>
        <div class="col-xs-11" style="padding-left:0;">
            <p><b>IP Info Info Service</b> is not active. <b>service_ip_info</b> must contain valid URL</p>
            <a href="{$lang_url}{$admin_url}?section=site_settings&edit_key=service_ip_info">{_t("admin.site_settings")}, Config name: service_ip_info</a>.
        </div>
    </div>
</div>
{/if}

{if !$service_ua_info}
<div class="alert alert-warning">
    <div class="row">
        <div class="col-xs-1"><img src="{CONTENT_URL}/{$msv_seo.preview}" class="img-thumbnail"></div>
        <div class="col-xs-11" style="padding-left:0;">
            <p><b>UserAgent Info Service</b> is not active. <b>service_ua_info</b> must contain valid URL</p>
            <a href="{$lang_url}{$admin_url}?section=site_settings&edit_key=service_ua_info">{_t("admin.site_settings")}, Config name: service_ua_info</a>.
        </div>
    </div>
</div>
{/if}
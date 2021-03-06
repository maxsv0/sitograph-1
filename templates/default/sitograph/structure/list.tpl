{assign var="listTable" value=$admin_list}

{if $listTable}
<div class="table-responsive">
<table id="structure-table" class="table table-hover table-striped table-module">
<tr>
<th style="width:20px;">
&nbsp;
</th>
<th{if $table_sort == "name"} class='colactive'{/if}>
<a {if $table_sort == "name"}{if $table_sortd == "asc"}class="arrow-up"{else}class="arrow-down"{/if}{/if} href="{$lang_url}{$admin_url}?section={$admin_section}&table={$admin_table}&sort=name&sortd={$table_sortd_rev}">{$t["table.structure.name"]}</a>

</th>
<th{if $table_sort == "url"} class='colactive'{/if}>
<a {if $table_sort == "url"}{if $table_sortd == "asc"}class="arrow-up"{else}class="arrow-down"{/if}{/if} href="{$lang_url}{$admin_url}?section={$admin_section}&table={$admin_table}&sort=url&sortd={$table_sortd_rev}">{$t["table.structure.url"]}</a>
</th>
<th class="text-nowrap">{$t["table.structure.page_template"]}</th>
<th{if $table_sort == "access"} class='colactive'{/if}>
<a {if $table_sort == "access"}{if $table_sortd == "asc"}class="arrow-up"{else}class="arrow-down"{/if}{/if} href="{$lang_url}{$admin_url}?section={$admin_section}&table={$admin_table}&sort=access&sortd={$table_sortd_rev}">{$t["table.structure.access"]}</a>
</th>
<th{if $table_sort == "sitemap"} class='colactive'{/if}>
<a {if $table_sort == "sitemap"}{if $table_sortd == "asc"}class="arrow-up"{else}class="arrow-down"{/if}{/if} href="{$lang_url}{$admin_url}?section={$admin_section}&table={$admin_table}&sort=sitemap&sortd={$table_sortd_rev}">{$t["table.structure.sitemap"]}</a>
</th>
<th{if $table_sort == "published"} class='colactive'{/if}>
<a {if $table_sort == "published"}{if $table_sortd == "asc"}class="arrow-up"{else}class="arrow-down"{/if}{/if} href="{$lang_url}{$admin_url}?section={$admin_section}&table={$admin_table}&sort=published&sortd={$table_sortd_rev}">{$t["table.structure.published"]}</a>
</th>
<th>{$t["actions"]}</th>
</tr>

{include file="$themePath/sitograph/structure/list-level.tpl" show_parent_id=0 level=1}

</div>
</table>
{else}

<div class="col-sm-6 col-md-offset-2">
<div class="alert alert-info">{$t["not_found"]}</div>
</div>

{/if} 

<div class="col-sm-6">
<a href="{$lang_url}{$admin_url}?section={$admin_section}&add_new" class="btn btn-primary"><span class="glyphicon glyphicon-ok">&nbsp;</span>{$t["btn.add_new"]}</a>
</div>



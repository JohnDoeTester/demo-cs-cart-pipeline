{*
array $storefront_admins Storefront adminitrators list
*}

{if $storefront_admins}
    <div class="table-responsive-wrapper longtap-selection">
        <table width="100%" class="table table-middle table--relative table-responsive table--overflow-hidden">
            <thead data-ca-bulkedit-default-object="true" data-ca-bulkedit-component="defaultObject">
                <tr>
                    <th width="25%">
                        {include file="common/table_col_head.tpl" type="name" text=__("person_name")}
                    </th>
                    <th width="25%">
                        {include file="common/table_col_head.tpl" type="email"}
                    </th>
                </tr>
            </thead>
            {foreach $storefront_admins as $admin}
                <tr class="cm-row-status-{$admin.status|lower} cm-longtap-target"
                    data-ca-id="{$admin.user_id}"
                >
                    <td width="25%" class="row-status wrap" data-th="{__("person_name")}">{if $admin.firstname || $admin.lastname}<a href="{"profiles.update?user_id=`$admin.user_id`&user_type=`$admin.user_type`"|fn_url}">{$admin.lastname} {$admin.firstname}</a>{else}-{/if}</td>
                    <td width="25%" data-th="{__("email")}"><a class="row-status" href="mailto:{$admin.email|escape:url}">{$admin.email}</a></td>
                </tr>
            {/foreach}
        </table>
    </div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
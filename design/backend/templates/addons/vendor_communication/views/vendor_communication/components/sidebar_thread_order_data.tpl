{if $object.object_type === $smarty.const.VC_OBJECT_TYPE_ORDER}
{$order_statuses = $smarty.const.STATUSES_ORDER|fn_get_statuses}

<div class="sidebar-row">
    <h6>{__("vendor_communication.order_details")}</h6>
    <ul class="unstyled">
        <li>
        {if fn_check_permissions("orders", "details", "admin")}
            <a href="{"orders.details?order_id=`$object.order_id`"|fn_url}" title="{__("order")} #{$object.order_id}">
                {__("order")} #{$object.order_id}
            </a>
        {else}
            {__("order")} #{$object.order_id}
        {/if}
        </li>
        <li>
            <span class="muted">
                {__("total")}:
            </span>
            <span class="pull-right">
                {include file="common/price.tpl" value=$object.total}
            </span>
        </li>
        <li>
            <span class="muted">
                {__("date")}:
            </span>
            <span class="pull-right">
                {$object.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
            </span>
        </li>
        <li>
            <span class="muted">
                {__("status")}:
            </span>
            <span class="pull-right">
                {$order_statuses[$object.status].description}
            </span>
        </li>
    </ul>
</div>
<div class="sidebar-row">
    <h6>{__("customer_information")}</h6>
    <div class="profile-info">
        {include_ext file="common/icon.tpl" class="icon-user"}
        <div>
            {if $object.email}<a href="mailto:{$object.email|escape:url}">@</a> {/if}
            {if $object.user_id}<a href="{"profiles.update?user_id=`$object.user_id`"|fn_url}">{/if}{$object.lastname} {$object.firstname}{if $object.user_id}</a>{/if}
        </div>
        {if $object}
            <div>
                <bdi><a href="tel:{$object.phone}">{$object.phone}</a></bdi>
            </div>
        {/if}
    </div>
</div>
{/if}

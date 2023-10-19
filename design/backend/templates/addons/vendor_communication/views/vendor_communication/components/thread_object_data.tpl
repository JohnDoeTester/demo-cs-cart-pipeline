{hook name="vendor_communication:thread_object_data"}
    {if $object && $object.object_type == $smarty.const.VC_OBJECT_TYPE_PRODUCT}
        {if $new_thread}
            <input type="hidden" name="thread[subject]" id="thread_message_subject_{$object_id}" value="{__("product")}:{$object.product}"/>
        {/if}
        {if fn_check_permissions("products", "update", "admin")}
            <a href="{"products.update?product_id=`$object.product_id`"|fn_url}"
               class="post-object" title="{$object.product}">
                {$object.product}
            </a>
        {else}
            <span class="post-object">{$object.product}</span>
        {/if}

        <div class="additional-info">/
            {hook name="vendor_communication:product_info"}
            {$object.product_code}
            {/hook}
            <span class="additional-text">/
                {include file="common/price.tpl" value=$object.price}</span>
        </div>
    {elseif $object && $object.object_type == $smarty.const.VC_OBJECT_TYPE_ORDER}
        {if $new_thread}
            <input type="hidden" name="thread[subject]" id="thread_message_subject_{$object_id}" value="{__("order")} #{$object.order_id}"/>
        {/if}
        {if fn_check_permissions("orders", "details", "admin")}
            <a href="{"orders.details?order_id=`$object.order_id`"|fn_url}"
               class="post-object" title="{__("order")} #{$object.order_id}">
                {__("order")} #{$object.order_id}
            </a>
        {else}
            <span class="post-object">{__("order")} #{$object.order_id}</span>
        {/if}

        <div class="additional-info">/
            {$object.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
        </div>
    {/if}
{/hook}
{** block-description:tmpl_call_request **}
<div class="ty-cr-phone-number-link">
    <div class="ty-cr-phone"><span><bdi><span class="ty-cr-phone-prefix">{$phone_number.prefix}</span>{$phone_number.postfix}</bdi></span><span class="ty-cr-work">{__("call_request.work_time")}</span></div>
    <div class="ty-cr-link">
        {$obj_prefix = "block"}
        {$obj_id = $block.snapping_id|default:0}

        {if $smarty.request.company_id}
            {$href="call_requests.request?obj_prefix=`$obj_prefix`&obj_id=`$obj_id`&company_id=`$company_id`"}
        {else}
            {$href="call_requests.request?obj_prefix=`$obj_prefix`&obj_id=`$obj_id`"}
        {/if}

        {include file="common/popupbox.tpl"
            href=$href
            link_text=__("call_requests.request_call")
            title=__("call_requests.request_call")
            id="call_request_{$obj_prefix}{$obj_id}"
            content=""
        }
    </div>
</div>
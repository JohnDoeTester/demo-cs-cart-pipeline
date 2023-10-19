{if $field_id === "phone" && $addons.call_requests.enable_call_requests_for_vendors == "YesNo::YES"|enum}
    <div class="ty-cr-link">
        {$obj_prefix = "block"}
        {$obj_id = $block.snapping_id|default:0}
        {include file="common/popupbox.tpl"
            href="call_requests.request?obj_prefix={$obj_prefix}&obj_id={$obj_id}&company_id={$company_data.company_id}"
            link_text=__("call_requests.request_call")
            text=__("call_requests.request_call")
            id="call_request_{$obj_prefix}{$obj_id}"
            content=""
        }
    </div>
{/if}
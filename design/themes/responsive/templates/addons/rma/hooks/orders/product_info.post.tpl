{if $product.returns_info}
    {if !$return_statuses}{assign var="return_statuses" value=$smarty.const.STATUSES_RETURN|fn_get_simple_statuses}{/if}
        <div class="ty-mtb-xs"><a class="cm-combination combination-link" id="sw_ret_{$key}">{include_ext file="common/icon.tpl" class="ty-icon-right-dir ty-dir-list" id="on_ret_`$key`" title=__("expand_sublist_of_items")}{include_ext file="common/icon.tpl" class="ty-icon-down-dir ty-dir-list hidden" id="off_ret_`$key`" title=__("collapse_sublist_of_items")}{__("returns_info")}</a></div>
    <div class="hidden" id="ret_{$key}">
        {foreach from=$product.returns_info item="amount" key="status" name="f_rinfo"}
            <p><strong>{$return_statuses.$status|default:""}</strong>:&nbsp;{$amount} {__("items")}</p>
        {/foreach}
    </div>
{/if}

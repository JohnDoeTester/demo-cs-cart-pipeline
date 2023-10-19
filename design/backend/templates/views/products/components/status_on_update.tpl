{$status = $obj.status|default:""}
{$items_status = $items_status|default:($status|fn_get_product_statuses:$hidden)}
{$non_editable = $non_editable_status|default:false}

{hook name="products:update_product_status_container"}
{if $non_editable || $display == "text" || $display == "select" || $display == "popup"}
    {capture name="status_title"}
        {$items_status.$status}
    {/capture}
{/if}
{if $display == "select"}
    <select class="input-small {if $meta}{$meta}{/if}"
            name="{$input_name}"
            {if $input_id}id="{$input_id}"{/if}
    >
        {foreach $items_status as $status_id => $status_name}
            <option value="{$status_id}"
                    {if $status === $status_id}
                        selected="selected"
                    {/if}
            >{$status_name}</option>
        {/foreach}
    </select>
{elseif $display == "popup"}
    <input {if $meta}class="{$meta}"{/if}
           type="hidden"
           name="{$input_name}"
           id="{$input_id|default:$input_name}"
           value="{$status}"
    />
    <div class="cm-popup-box btn-group dropleft">
        <a id="sw_{$input_name}" class="dropdown-toggle btn-text" data-toggle="dropdown">
            {$smarty.capture.status_title nofilter}
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu cm-select">
            {$items_status = $status|fn_get_default_statuses:$hidden}
            {foreach $items_status as $status_id => $status_name}
                <li {if $status == $status_id}class="disabled"{/if}>
                    <a class="status-link-{$status_id|lower} {if $status == $status_id}active{/if}"
                       onclick="return fn_check_object_status(this, '{$status_id|lower}', '{if $statuses}{$statuses[$status_id].color|default:''}{/if}');"
                       data-ca-result-id="{$input_id|default:$input_name}"
                    >
                        {$status_name}
                    </a>
                </li>
            {/foreach}
        </ul>
    </div>
    {if !$smarty.capture.avail_box}
        {script src="js/tygh/select_popup.js"}
        {capture name="avail_box"}Y{/capture}
    {/if}
{elseif $non_editable || $display == "text"}
    <div class="control-group">
        <label class="control-label cm-required">{__("status")}:</label>
        <div class="controls">
            <div class="text-type-value {$product_status_style}">{$smarty.capture.status_title nofilter}</div>
        </div>
    </div>
{else}
<div class="control-group">
    <label class="control-label cm-required">{__("status")}:</label>
    <div class="controls" {$data_product_statuses|render_tag_attrs nofilter}>
    
        {foreach from=$items_status item="status_name" key="status_id" name="status_cycle"}
            <label class="radio inline" for="{$id}_{$obj_id|default:0}_{$status_id|lower}">
                <input type="radio"
                       name="{$input_name}"
                       class="product__status-switcher"
                       id="{$id}_{$obj_id|default:0}_{$status_id|lower}"
                       {if $status === $status_id || (!$status && $smarty.foreach.status_cycle.first)}checked="checked"{/if}
                       value="{$status_id}"
                />
                {$status_name}
            </label>
        {/foreach}
    </div>
</div>
{/if}
{/hook}

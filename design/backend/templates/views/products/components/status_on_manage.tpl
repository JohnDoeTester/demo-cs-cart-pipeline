{$items_status = $items_status|default:($status|fn_get_product_statuses:$hidden)}
{$statuses = $statuses|default:[]}
{$dynamic_object = $dynamic_object|default:""}
{$non_editable = $non_editable_status|default:false}
{$popup_additional_class = $popup_additional_status_class|default:""}

{hook name="products:status_name_container"}
{if $non_editable || $display == "text"}
    <span class="view-status">
        {hook name="products:status_name"}
            {$items_status.$status|default:$default_status_text}
        {/hook}
    </span>
{else}
    {$prefix = $prefix|default:"select"}
    {$btn_meta = $btn_meta|default:"btn-text"}
    {$status_target_id = $status_target_id|default:($st_result_ids|default:"")}
    {$update_controller = $update_controller|default:"tools"}

    <div class="cm-popup-box {if !$hide_for_vendor}dropdown{/if} dropleft {$popup_additional_class}"
        id="product_status_{$id}_select">
        {hook name="products:status_name"}
        {if !$hide_for_vendor}
            <a href="#"
                {if $id}id="sw_{$prefix}_{$id}_wrap"{/if}
                class="{$btn_meta} btn dropdown-toggle {if $id}cm-combination{/if}"
                data-toggle="dropdown"
            >
        {/if}
            {$items_status.$status|default:$default_status_text}
        {if !$hide_for_vendor}
            <span class="caret"></span>
            </a>
        {/if}
        {/hook}

        {if $id && !$hide_for_vendor}
            <ul class="dropdown-menu">
                {$extra_params = "&table={$table}&id_name={$object_id_name}"}
                {if $st_return_url}
                    {$extra_params = "{$extra_params}&redirect_url={$st_return_url|escape:url}"}
                {/if}

                {foreach $items_status as $status_id => $status_name}
                    <li {if $status == $status_id}class="disabled"{/if}>
                        {hook name="products:status_select_item"}
                            <a class="status-link-{$status_id|lower} {$status_meta} {if $confirm}cm-confirm{/if} {if $status == $status_id}active{else}cm-ajax cm-post {if $ajax_full_render}cm-ajax-full-render{/if}{/if}"
                            {if $status_target_id}
                                data-ca-target-id="{$status_target_id}"
                            {/if}
                            href="{fn_url("{$update_controller}.update_status?id={$id}&status={$status_id}{$extra_params}{$dynamic_object}")}"
                            onclick="return fn_check_object_status(this, '{$status_id|lower}', '{if $statuses}{$statuses[$status_id].params.color|default:''}{/if}');"
                            >
                            {$status_name}
                            </a>
                        {/hook}
                    </li>
                {/foreach}
            </ul>

            {if !$smarty.capture.avail_box}
                {script src="js/tygh/select_popup.js"}
                {capture name="avail_box"}Y{/capture}
            {/if}
        {/if}
    <!--product_status_{$id}_select--></div>
{/if}
{/hook}

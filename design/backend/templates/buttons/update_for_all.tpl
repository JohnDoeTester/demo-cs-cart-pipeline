{if $display}
    {$absolute_position = $absolute_position|default:false}
    {$append = $append|default:false}
    {$hide_inputs = $hide_inputs|default:false}

    {if $hide_element}
        {$title_act = __("update_for_all_hid_act")}
        {$title_dis = __("update_for_all_hid_dis")}
    {else}
        {$title_act = __("update_for_all_act")}
        {$title_dis = __("update_for_all_dis")}
    {/if}
    {if $settings.Stores.default_state_update_for_all == 'active'}
        {$title = $title_act}
        {$visible = "visible"}
    {else}
        {$title = $title_dis}
    {/if}
    {if $runtime.simple_ultimate}
        {$visible = "hidden"}
    {/if}

    {if $object_ids}
        {$disable_ids = $object_ids|to_json}
        {$component = $component|default:($object_ids|implode:"__")}
    {else}
        {$disable_ids = $object_id}
        {$component = $component|default:$object_id}
        {$object_ids = [$object_id]}
        {$names = [$object_id => $name]}
    {/if}
    {if $append && !$hide_inputs}
        {$static_position = $static_position|default:true}
        {$meta = ($meta) ? "`$meta` update-for-all__icon--append" : "update-for-all__icon--append" }
        {$meta_container = ($meta_container) ? "`$meta_container` btn update-for-all--append" : "btn update-for-all--append" }
    {/if}

    <div class="update-for-all
        {if $absolute_position}
            update-for-all--absolute
        {elseif $static_position}
            update-for-all--static
        {/if}
        {$meta_container}
    ">
        <a class="cm-update-for-all-icon
            icon-group
            update-for-all__icon
            cm-tooltip
            {$visible}
            {$meta}"
            href="#"
            title="{$title}"
            data-ca-update-for-all="{$component}"
            data-ca-title-active="{$title_act}"
            data-ca-title-disabled="{$title_dis}"
            data-ca-disable-id="{$disable_ids}"
            {if $hide_element}data-ca-hide-id="{$hide_element}"{/if}>
        </a>
        {foreach $names as $object_id => $name}
            <input type="hidden"
                class="cm-no-hide-input"
                id="hidden_update_all_vendors_{$object_id}"
                name="{$name}"
                value="{"YesNo::YES"|enum}"
                {if $settings.Stores.default_state_update_for_all === "not_active" && !$runtime.simple_ultimate}
                    disabled="disabled"
                {/if}
            />
        {/foreach}
    </div>
{/if}

{$storefront = $storefront_id|fn_get_storefront}
{$storefront_name = $storefront->name|default:"{$ldelim}storefront{$rdelim}"}
{$show_text_storefront_names = $view_only || !fn_check_view_permissions("storefronts.update", "GET")}

{if !empty($auth.storefront_id) && fn_allowed_for('MULTIVENDOR:ULTIMATE')}
    {$view_only = true}
{/if}

{if $multiple}
    <tr {if !$clone}id="{$holder}_{$storefront_id}"{/if}
        class="cm-js-item storefront {if $clone}cm-clone hidden{/if}"
        data-ca-storefront-id="{$storefront->storefront_id}"
        data-ca-storefront-company-ids="{$storefront->getCompanyIds()|json_encode}"
    >
        {if $position_field}
            <td data-th="&nbsp;">
                <input type="text"
                       name="{$input_name}[{$storefront_id}]"
                       value="{$position * 10}"
                       size="3"
                       class="input-micro" {if $clone}disabled="disabled"{/if}
                />
            </td>
        {/if}

        <td data-th="&nbsp;">
            {if $show_text_storefront_names}
                {$storefront_name}
            {else}
                <a class="storefront__name"
                   href="{"storefronts.update?storefront_id=`$storefront_id`"|fn_url}"
                >{$storefront_name}</a>
            {/if}

        </td>

        <td data-th="&nbsp;">
            <div class="hidden-tools storefront__tools">
                {if !$hide_delete_button && !$view_only && $storefront_owner_id != $storefront_id}
                    {capture name="tools_list"}
                        <li>
                            {btn type="list"
                                text=__("remove")
                                onclick="Tygh.$.cePicker('delete_js_item', '{$holder}', '{$storefront_id}', 'a'); return false;"
                            }
                        </li>
                    {/capture}
                    {dropdown content=$smarty.capture.tools_list}
                {/if}
            </div>
        </td>
        {if !$hide_input}
            <input {if $input_id}id="{$input_id}"{/if}
                   type="hidden"
                   name="{$input_name}"
                   value="{$storefront_id}"
            />
        {/if}
    </tr>
{else}
    <span {if !$clone}id="{$holder}_{$storefront_id}"{/if}
          class="cm-js-item no-margin {if $clone}cm-clone hidden{/if}"
    >
        {if !$first_item && $single_line}
            <span class="cm-comma {if $clone}hidden{/if}">,&nbsp;&nbsp;</span>
        {/if}
        <input class="cm-picker-value-description {$extra_class}"
               type="text"
               value="{$storefront_name}"
               {if $display_input_id}id="{$display_input_id}"{/if}
               size="10"
               name="storefront_name"
               readonly="readonly"
               {$extra}
        >&nbsp;
    </span>
{/if}

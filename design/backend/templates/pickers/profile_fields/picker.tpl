{$rnd = rand()}
{$data_id = "`$data_id`_`$rnd`"}
{$view_mode = $view_mode|default:"mixed"}

{script src="js/tygh/picker.js"}

{if $item_ids && !$item_ids|is_array}
    {$item_ids = ","|explode:$item_ids}
{/if}

{if $view_mode != "list"}
    <div class="clearfix">
        {if $extra_var}
            {$extra_var = $extra_var|escape:url}
        {/if}

        {if !$no_container}<div class="buttons-container pull-right">{/if}{if $picker_view}[{/if}
            {$exclude_names = $exclude_names|default:[]}
            {$include_names = $include_names|default:[]}
            {$exclude_types = $exclude_types|default:[]}
            {include file="buttons/button.tpl" but_id="opener_picker_`$data_id`" but_href="profile_fields.picker?section={$section}&exclude_names={","|implode:$exclude_names}&include_names={","|implode:$include_names}&exclude_types={","|implode:$exclude_types}&data_id={$data_id}"|fn_url but_text=$but_text|default:__("add_profile_fields") but_role="add" but_target_id="content_`$data_id`" but_meta="btn cm-dialog-opener" but_icon="icon-plus"}
        {if $picker_view}]{/if}{if !$no_container}</div>{/if}
        <div class="hidden" id="content_{$data_id}" title="{$but_text|default:__("add_profile_fields")}">
        </div>
    </div>
{/if}

<input id="pf_{$data_id}_ids" type="hidden" name="{$input_name}" value="{if $item_ids}{","|implode:$item_ids}{/if}" />
<div class="clearfix"></div>
<div class="table-responsive-wrapper">
    <table class="table table-middle table--relative table-responsive">
        <thead>
            <tr>
                <td width="1%"></td>
                <th width="15%">{__("id")}</th>
                <th width="60%">{__("description")}</th>
                <th width="15%" {if $adjust_requireability === false}class="hidden"{/if}>{__("required")}</th>
                {if !$view_only}<th>&nbsp;</th>{/if}
            </tr>
        </thead>
        <tbody id="{$data_id}"{if !$item_ids} class="hidden"{/if}{if $sortable} data-cm-sortable-profile-fields-picker-container="true" data-ca-sortable-item-class="profile-field-picker__sortable-row" data-ca-data-id="{$data_id}"{/if}>
        {include file="pickers/profile_fields/js.tpl" field_id="`$ldelim`field_id`$rdelim`" description="`$ldelim`description`$rdelim`" holder=$data_id clone=true}
        {foreach $item_ids as $field_id}
            {$profile_field = fn_get_profile_field($field_id)}

            {include file="pickers/profile_fields/js.tpl"
                field_id=$field_id
                field_name=$profile_field.field_name
                description=$profile_field.description
                required=$profile_field.checkout_required
                disable_required=$disable_required
                holder=$data_id
            }
        {/foreach}
        </tbody>
        <tbody id="{$data_id}_no_item"{if $item_ids} class="hidden"{/if}>
        <tr>
            <td colspan="{if !$view_only}5{else}4{/if}" data-th="&nbsp;"><p class="no-items">{$no_item_text|default:__("no_items") nofilter}</p></td>
        </tr>
        </tbody>
    </table>
</div>

{script src="js/tygh/backend/pickers/profile_fields.js"}

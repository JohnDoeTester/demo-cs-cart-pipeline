{$first_id=""|uniqid}
{$second_id=""|uniqid}

{include file="common/subheader.tpl" title=$title}
<div class="table-wrapper">
    <table class="{$class_name}" width="100%">
    <tr>
        <td width="48%">
            <label for="id_{$first_id}" class="{if $required}cm-required{/if} cm-all hidden"></label>
            <select name="{$first_name}[]" id="id_{$first_id}" size="10" value="" multiple="multiple" class="input-full">
                {foreach from=$first_data key=key item=value}
                    <option value="{$key}">{$value}</option>
                {/foreach}
            </select>

            {if $sortable}
            <p>
                <span onclick="Tygh.$('#id_{$first_id}').swapOptions('up');" class="icon-chevron-up hand" ></span>
                <span onclick="Tygh.$('#id_{$first_id}').swapOptions('down');" class="icon-chevron-down hand" ></span>
            </p>
            {/if}
        </td>
        <td class="center chevron-icons" width="4%">
                <span onclick="Tygh.$('#id_{$second_id}').moveOptions('#id_{$first_id}');" class="icon-chevron-left hand clear"></span><br/>
                <span onclick="Tygh.$('#id_{$first_id}').moveOptions('#id_{$second_id}');" class="icon-chevron-right hand"></span>
        </td>
        <td width="48%" valign="top">
            <select name="{$second_name}" id="id_{$second_id}" size="10" value="" multiple="multiple" class="input-full">
                {foreach from=$second_data key=key item=value}
                    <option value="{$key}">{$value}</option>
                {/foreach}
            </select>
        </td>
    </tr>
    </table>
</div>
<div class="muted description">{__("multiple_selectbox_notice") nofilter}</div>

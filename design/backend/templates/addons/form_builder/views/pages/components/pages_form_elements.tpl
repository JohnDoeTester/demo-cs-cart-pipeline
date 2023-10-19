{literal}
<script>
    function fn_check_element_type(elm, id, selectable_elements)
    {
        var $ = Tygh.$;
        var elem_id = id.replace('elm_', 'box_element_variants_');
        $('#' + elem_id).toggleBy(selectable_elements.indexOf(elm) == -1);

        // Hide description box for separator
        $('#descr_' + id).toggleBy((elm == 'D'));
        $('#hr_' + id).toggleBy((elm != 'D'));

        $('#req_' + id).prop('disabled', (elm == 'D' || elm == 'H'));
    }

    function fn_go_check_element_type(id, selectable_elements)
    {
        var $ = Tygh.$;
        var id = id || '';

        var c = parseInt(id.replace('add_elements', '').replace('_', ''));
        c = (isNaN(c))? 1 : c++;
        var c_id = c.toString();
        $('#elm_add_variants_' + c_id).trigger('change');
    }
</script>
{/literal}

{assign var="allow_save" value=true}
{if "ULTIMATE"|fn_allowed_for}
    {assign var="allow_save" value=$page_data|fn_allow_save_object:"pages"}
{/if}

<div class="table-responsive-wrapper">
    <table class="table hidden-inputs table-middle table--relative table-responsive">
    <thead>
        <tr>
            <th width="3%">&nbsp;</th>
            <th width="4%">{__("position_short")}</th>
            <th width="25%">{__("name")}</th>
            <th width="25%">{__("type")}</th>
            <th width="12%">{__("required")}</th>
            <th width="25%">&nbsp;</th>
            <th width="6%" class="right">{__("status")}</th>
        </tr>
    </thead>
    {foreach from=$elements item="element" name="fe_e"}
    {assign var="num" value=$smarty.foreach.fe_e.iteration}
    <tbody class="cm-row-item cm-row-status-{$element.status|lower}">
    <tr>
        <td data-th="&nbsp;">
            <div id="on_box_element_variants_{$element.element_id}" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hand btn cm-combination-options-{$id}"><span class="icon-caret-right"></span></div>
            <div id="off_box_element_variants_{$element.element_id}" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hand btn hidden cm-combination-options-{$id}"><span class="icon-caret-down"></span> </div>
        </td>
        <td class="nowrap" data-th="{__("position_short")}">
            <input type="hidden" name="page_data[form][elements_data][{$num}][element_id]" value="{$element.element_id}" />
            <input class="input-micro" type="text" size="3" name="page_data[form][elements_data][{$num}][position]" value="{$element.position}" /></td>
        <td data-th="{__("name")}">
            <input id="descr_elm_{$element.element_id}" class="{if $element.element_type == $smarty.const.FORM_SEPARATOR}hidden{/if}" type="text" name="page_data[form][elements_data][{$num}][description]" value="{$element.description}" />
            <hr id="hr_elm_{$element.element_id}" width="100%" {if $element.element_type != $smarty.const.FORM_SEPARATOR}class="hidden"{/if} /></td>
        <td data-th="{__("type")}">
            {include file="addons/form_builder/views/pages/components/element_types.tpl" element_type=$element.element_type elm_id=$element.element_id}</td>
        <td class="center" data-th="{__("required")}">
            <input type="hidden" name="page_data[form][elements_data][{$num}][required]" value="N" />
            <input id="req_elm_{$element.element_id}" type="checkbox" {if "HD"|strstr:$element.element_type}disabled="disabled"{/if} name="page_data[form][elements_data][{$num}][required]" value="Y" {if $element.required == "Y"}checked="checked"{/if} /></td>
        <td data-th="&nbsp;">
            {include file="buttons/multiple_buttons.tpl" only_delete="Y"}
        </td>
        <td class="right" data-th="{__("status")}">
            {include file="common/select_popup.tpl" id=$element.element_id prefix="elm" status=$element.status hidden="" object_id_name="element_id" table="form_options" non_editable=!$allow_save}
        </td>
    </tr>
    <tr id="box_element_variants_{$element.element_id}" class="{if !$selectable_elements|substr_count:$element.element_type}hidden{/if} row-more row-gray hidden">
        <td>&nbsp;</td>
        <td colspan="5">
            <div class="table-responsive-wrapper">
                <table class="table table-middle table--relative table-responsive">
                <thead>
                    <tr class="cm-first-sibling">
                        <th width="5%" class="left">{__("position_short")}</th>
                        <th>{__("variant")}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                {foreach from=$element.variants item=var key="vnum"}
                <tr class="cm-first-sibling cm-row-item">
                    <td data-th="{__("position_short")}">
                        <input type="hidden" name="page_data[form][elements_data][{$num}][variants][{$vnum}][element_id]" value="{$var.element_id}" />
                        <input class="input-micro" size="3" type="text" name="page_data[form][elements_data][{$num}][variants][{$vnum}][position]" value="{$var.position}" /></td>
                    <td data-th="{__("variant")}"><input type="text" class="span7" name="page_data[form][elements_data][{$num}][variants][{$vnum}][description]" value="{$var.description}" /></td>
                    <td data-th="&nbsp;">
                        {include file="buttons/multiple_buttons.tpl" only_delete="Y"}
                    </td>
                </tr>
                {/foreach}
                {math equation="x + 1" assign="vnum" x=$vnum|default:0}
                <tr id="box_elm_variants_{$element.element_id}" class="cm-row-item cm-elm-variants">
                    <td data-th="{__("position_short")}"><input class="input-micro" size="3" type="text" name="page_data[form][elements_data][{$num}][variants][{$vnum}][position]" /></td>
                    <td data-th="{__("variant")}"><input type="text" class="span7" name="page_data[form][elements_data][{$num}][variants][{$vnum}][description]" /></td>
                    <td data-th="&nbsp;">
                        {include file="buttons/multiple_buttons.tpl" item_id="elm_variants_`$element.element_id`" tag_level="5"}
                    </td>
                </tr>
                </table>
            </div>
        </td>
        <td>&nbsp;</td>
    </tr>
    </tbody>
    {/foreach}

    {math equation="x + 1" assign="num" x=$num|default:0}
    <tbody class="cm-row-item cm-row-status-a" id="box_add_elements">
    <tr class="no-border">
        <td data-th="&nbsp;">&nbsp;</td>
        <td class="right" data-th="{__("position_short")}">
            <input class="input-micro" size="3" type="text" name="page_data[form][elements_data][{$num}][position]" value="" /></td>
        <td data-th="{__("name")}">
            <input id="descr_elm_add_variants" type="text" name="page_data[form][elements_data][{$num}][description]" value="" />
            <hr id="hr_elm_add_variants" class="hidden" /></td>
        <td data-th="{__("type")}">
            {include file="addons/form_builder/views/pages/components/element_types.tpl" element_type="" elm_id="add_variants"}</td>
        <td class="center" data-th="{__("required")}">
            <input type="hidden" name="page_data[form][elements_data][{$num}][required]" value="N" />
            <input id="req_elm_add_variants" type="checkbox" name="page_data[form][elements_data][{$num}][required]" value="Y" checked="checked" /></td>
        <td class="left" data-th="&nbsp;">
            {include file="buttons/multiple_buttons.tpl" item_id="add_elements" on_add="fn_go_check_element_type();"}
        </td>
        <td class="right" data-th="{__("status")}">
            {include file="common/select_status.tpl" input_name="page_data[form][elements_data][`$num`][status]" display="popup"}
        </td>
    </tr>
    <tr id="box_element_variants_add_variants" class="row-more row-gray">
        <td>&nbsp;</td>
        <td colspan="5">
            <div class="table-responsive-wrapper">
                <table class="table table-middle table--relative table-responsive">
                <thead>
                    <tr class="cm-first-sibling">
                        <th width="5%" class="left">{__("position_short")}</th>
                        <th>{__("description")}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tr id="box_elm_variants_add_variants" class="cm-row-item cm-elm-variants">
                    <td data-th="{__("position_short")}"><input class="input-micro" size="3" type="text" name="page_data[form][elements_data][{$num}][variants][0][position]" /></td>
                    <td data-th="{__("description")}"><input class="span7" type="text" name="page_data[form][elements_data][{$num}][variants][0][description]" /></td>
                    <td data-th="&nbsp;">
                        {include file="buttons/multiple_buttons.tpl" item_id="elm_variants_add_variants" tag_level="5"}
                    </td>
                </tr>
                </table>
            </div>
        </td>
        <td>&nbsp;</td>
    </tr>
    </tbody>


    </table>
</div>

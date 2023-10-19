{if !$smarty.request.extra}
<script>
(function(_, $) {

    _.tr('text_items_added', '{__("text_items_added")|escape:"javascript"}');
    _.tr('options', '{__("options")|escape:"javascript"}');
    _.tr('no', '{__("no")|escape:"javascript"}');
    _.tr('yes', '{__("yes")|escape:"javascript"}');
    _.tr('aoc', '{__("any_option_combinations")|escape:"javascript"}');

    function _getDescription(obj, id)
    {
        var p = {};
        var d = '';
        var aoc = $('#sw_option_' + id + '_AOC', obj);
        var aocDescription;
        var aocIsChecked = 'N';
        var descriptionPrefix;

        if (aoc.length && aoc.prop('checked')) {
            aocDescription = aoc.data('caAocText') ? aoc.data('caAocText') : _.tr('aoc');
            aocIsChecked = 'Y';
        } else {
            var $container = $('#option_' + id + '_AOC', obj);
                descriptionPrefix = $container.data('caEmptyProductDescriptionPrefix') ? '' : (_.tr('options') + ': ');

            $(':input', $container).each( function() {
                var op = this;
                var j_op = $(this);

                if (typeof(op.name) == 'string' && op.name == '') {
                    return false;
                }
                var matches = op.name.match(/\[(\d+)\]$/);
                if (matches && matches.length) {
                    var option_id = matches[1];
                    if (op.type == 'checkbox') {
                        var variant = (op.checked == false) ? _.tr('no') : _.tr('yes');
                    }
                    if (op.type == 'radio' && op.checked == true) {
                        var variant = $('#option_description_' + id + '_' + option_id + '_' + op.value, obj).text();
                    }
                    if (op.type == 'select-one') {
                        var variant = op.options[op.selectedIndex].text;
                    }
                    if ((op.type == 'text' || op.type == 'textarea') && op.value != '') {
                        if (j_op.hasClass('cm-hint') && op.value == op.defaultValue) { //FIXME: We should not become attached to cm-hint class
                            var variant = '';
                        } else {
                            var variant = op.value;
                        }
                    }
                    if ((op.type == 'checkbox') || ((op.type == 'text' || op.type == 'textarea') && op.value != '') || (op.type == 'select-one') || (op.type == 'radio' && op.checked == true)) {
                        if (op.type == 'checkbox') {
                            p[option_id] = (op.checked == false) ? $('#unchecked_' + id + '_option_' + option_id, obj).val() : op.value;
                        }else{
                            p[option_id] = (j_op.hasClass('cm-hint') && op.value == op.defaultValue) ? '' : op.value; //FIXME: We should not become attached to cm-hint class
                        }

                        var optionName = $('#option_description_' + id + '_' + option_id, obj).text();

                        d += '<li><strong>' + optionName + '</strong>: <bdi>' + variant + '</bdi></li>';
                    }
                }
            });

            d = d ? '<strong>' + descriptionPrefix + '</strong><br><ul class="unstyled">' + d + '</ul>' : '';
        }
        return {
            path: p,
            desc: aocDescription ? aocDescription : d,
            aoc: aocIsChecked,
        };
    }

    $.ceEvent('on', 'ce.formpost_add_products', function(frm, elm) {
        var products = {};
        var _display = frm.find("[name=display_type]").val();

        if ($('input.cm-item:checked', frm).length > 0) {
            $('input.cm-item:checked', frm).each( function() {
                var id = $(this).val(),
                    value = ($('#product_' + id + '_alt').length && $('#product_' + id + '_alt').is(':enabled'))
                        ? $('#product_' + id + '_alt').val()
                        : $('#product_' + id).val();

                if (_display == "options" || _display == "options_amount" || _display == "options_price") {

                    products[id] = {
                        option: _getDescription(frm, id),
                        value: value
                    };
                } else if(_display === 'radio') {
                    products[id] = value;
                } else {
                    products[id] = {
                        value: value
                    };
                }

                if ($('#company_id_' + id).length) {
                    products[id].companyId = $('#company_id_' + id).val();
                    products[id].companyName = $('#company_name_' + id).val();
                }
            });

            $.ceEvent('trigger', 'ce.picker_transfer_js_items', [products, frm]);
            $.cePicker('add_js_item', frm.data('caResultId'), products, 'p', {});

            $.ceNotification('show', {
                type: 'N',
                title: _.tr('notice'),
                message: _.tr('text_items_added'),
                message_state: 'I'
            });
        }

        return false;
    });

}(Tygh, Tygh.$));
</script>
{/if}

{include file="views/products/components/products_search_form.tpl" dispatch="products.picker" picker_selected_companies=$picker_selected_companies extra="<input type=\"hidden\" name=\"result_ids\" value=\"pagination_`$smarty.request.data_id`\">" put_request_vars=true form_meta="cm-ajax" in_popup=true is_order_management=$is_order_management}

<form action="{$smarty.request.extra|to_relative_url|fn_url}" method="post" name="add_products" data-ca-result-id="{$smarty.request.data_id}" enctype="multipart/form-data">
<input type="hidden" name="display_type" value="{$smarty.request.display}">

{$but_text = __("add_products")}
{$but_close_text = __("add_products_and_close")}

{if $smarty.request.display != "options_amount" && $smarty.request.display != "options_price"}
    {$hide_amount = true}
{/if}

{if $smarty.request.display == "options_price"}
    {$show_price = true}
{/if}

{if $smarty.request.display == "radio"}
    {$show_radio = true}
    {$hide_options = true}
    {$but_text = ""}
    {$but_close_text = __("choose")}
{/if}

{include file="views/products/components/products_list.tpl"
    products=$products
    form_name="add_products"
    checkbox_id="add_product_checkbox"
    div_id="pagination_`$smarty.request.data_id`"
    hide_amount=$hide_amount
    show_price=$show_price
    checkbox_name=$smarty.request.checkbox_name
    show_aoc=$smarty.request.aoc
    only_selectable_options=$smarty.request.only_selectable_options
    additional_class="option-item"
    show_radio=$show_radio
    hide_options=$hide_options
    is_order_management=$is_order_management
}

{if $products}
<div class="buttons-container">
    {if $is_order_management}
        <a class="btn cm-dialog-closer tool-link" id="close_add_products">{__("close")}</a>
        {include file="buttons/button.tpl" but_name="submit" but_text=$but_close_text but_role="button_main" but_meta="cm-process-items btn-primary" but_onclick="$('#add_product_id').val('')" allow_href=true}
    {else}
        {include file="buttons/add_close.tpl" but_text=$but_text but_close_text=$but_close_text is_js=$smarty.request.extra|fn_is_empty}
    {/if}
</div>
{/if}
</form>

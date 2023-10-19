<div id="add_product">
{include file="common/pagination.tpl" div_id="pagination_`$smarty.request.data_id`"}

{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$rev="pagination_`$smarty.request.data_id`"|default:"pagination_contents"}

{$row_index=1}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
{script src="js/tygh/exceptions.js"}

{* add-new *}
{if $products}
<input type="hidden" id="add_product_id" name="product_id" value=""/>
<div class="table-responsive-wrapper">
    <table width="100%" class="table table--relative table-responsive">
    <thead>
    <tr>
        {hook name="product_list:table_head"}
        {if $hide_amount}
            <th class="center" width="1%">
                {if $show_radio}&nbsp;{else}{include file="common/check_items.tpl"}{/if}
            </th>
        {/if}
        <th width="80%"><a class="cm-ajax" href="{"`$c_url`&sort_by=product&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("product_name")}{if $search.sort_by === "product"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        {if $show_price}
            <th class="right" width="10%"><a class="cm-ajax" href="{"`$c_url`&sort_by=price&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("price")}{if $search.sort_by === "price"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
        {/if}
        {if !$hide_amount}
            <th class="center" width="5%">{__("quantity")}</th>
        {/if}
        {if $is_order_management}
            <th class="center" width="5%"></th>
        {/if}
        {/hook}
    </tr>
    </thead>
    {foreach $products as $product}
        {include file="views/products/components/products_list_row.tpl" row_index=$row_index++ hide_amount=$hide_amount}
    {/foreach}
    </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

<script>
(function(_, $) {
    function _switchAOC(id, disable, $row)
    {
        var aoc = $row.find('#sw_option_' + id + '_AOC');
        if (aoc.length) {
            aoc.addClass('cm-skip-avail-switch');
            aoc.prop('disabled', disable);
            disable = aoc.prop('checked') ? true : disable;
        }

        $row.find('.cm-picker-product-options').switchAvailability(disable, false);
    }

    function init(context)
    {
        if (context.find('tr[id^=picker_product_row_]').length) {
            if (!$('.cm-add-product').length) {
                context.find('.cm-picker-product-options').switchAvailability(true, false);
            } else {
                context.find('.cm-picker-product-options').switchAvailability(false, false);
            }
        }
    }

    $(document).ready(function() {
        init($(_.doc));

        $.ceEvent('on', 'ce.commoninit', function(context) {
            init(context);
        });

        $(_.doc).on('click', '.cm-increase,.cm-decrease', function() {
            var inp = $('input', $(this).closest('.cm-value-changer'));
            var new_val = parseInt(inp.val()) + ($(this).is('a.cm-increase') ? 1 : -1);
            var disable = new_val > 0 ? false : true;
            var _id = inp.prop('id').replace('product_id_', '');

            _switchAOC(_id, disable, $(this).closest('tr'));
        });

        $.ceEvent('on', 'ce.formajaxpost_add_products', function(response, params) {
            if ($('.cm-add-product').length && response.current_url) {
                var url = response.current_url;

                $.ceAjax('request', url, {
                    method: 'get',
                    result_ids: 'button_trash_products,om_ajax_update_totals,om_ajax_update_payment,om_ajax_update_shipping',
                    full_render: true
                });
            }
        });

        $(_.doc).on('click', '.cm-add-product', function() {
            if ($(this).prop('id')) {
                var _id = $(this).prop('id');
                $('#add_product_id').val(_id);
            }
        });

        $(_.doc).on('change', '.cm-amount', function() {
            var new_val = parseInt($(this).val());
            var disable = new_val > 0 ? false : true;
            var _id = $(this).prop('id').replace('product_id_', '');

            _switchAOC(_id, disable, $(this).closest('tr'));
        });

        $(_.doc).on('click', '.cm-item', function() {
            var disable = (this.checked) ? false : true;
            var _id = $(this).prop('id').replace('checkbox_id_', '');

            _switchAOC(_id, disable, $(this).closest('tr'));
        });

        $(_.doc).on('click', '.cm-check-items', function() {
            var form = $(this).parents('form:first');
            var _checked = this.checked;
            $('.cm-item', form).each(function () {
                if (_checked && !this.checked || !_checked && this.checked) {
                    $(this).click();
                }
            });
        });
    });
}(Tygh, Tygh.$));
</script>

{include file="common/pagination.tpl" div_id="pagination_`$smarty.request.data_id`"}

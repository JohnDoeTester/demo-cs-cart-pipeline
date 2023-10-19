<table class="table table-tree table--relative">
    <thead>
    <tr>
        <th width="1%">{include file="common/check_items.tpl"}</th>
        <th width="99%">
            &nbsp;{__("product_variations.variations")}
        </th>
    </tr>
    </thead>
    {foreach $combinations as $combination_id => $combination}
        {include file="addons/product_variations/views/product_variations/components/feature_combinations_list_row.tpl"
            level=0
            combination=$combination
            combination_id=$combination_id
        }
    {/foreach}
</table>

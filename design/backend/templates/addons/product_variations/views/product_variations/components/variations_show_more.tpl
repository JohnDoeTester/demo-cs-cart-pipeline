<tr>
    <td colspan="{math equation="x+6" x=$selected_features|@count}" class="product-variations__table-show-more-td">
        <div class="product-variations__table-show-more">
            {include file="buttons/button.tpl"
                but_text="{__("show_more")}"
                but_role="action"
                but_name="dispatch[product_variations.product_groups.show_more]"
                but_target_form="product_variations_update_form"
                but_meta="cm-submit cm-product-variations__show-more"
            }
        </div>
    </td>
</tr>
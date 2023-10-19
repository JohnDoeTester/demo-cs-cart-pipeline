{if !$bundle_promotions || !in_array($promotion_id, $bundle_promotions)}
    <p><a href="{"promotions.update?promotion_id=`$promotion_id`"|fn_url}">{__("details")}</a></p>
{else}
    {foreach $bundles as $bundle}
        {if $bundle.linked_promotion_id != $promotion_id}
            {continue}
        {/if}
        {$selected_bundle = $bundle}
    {/foreach}
    {if $selected_bundle}
        {capture name="add_new_picker"}
            <div id="add_new_bundle">
                {include file="addons/product_bundles/views/product_bundles/update.tpl"
                    product_id=$product_data.product_id
                    item=$selected_bundle
                }
            </div>
        {/capture}
        {include file="common/popupbox.tpl"
            id="add_new_bundle"
            content=$smarty.capture.add_new_picker
            link_text=__("product_bundles.bundle_details")
            act="edit"
        }

    {/if}
{/if}

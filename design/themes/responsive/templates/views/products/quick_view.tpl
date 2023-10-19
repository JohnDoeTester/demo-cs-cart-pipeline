<div class="ty-quick-view__wrapper">
    {assign var="quick_view" value="true"}
    {capture name="val_hide_form"}{/capture}
    {capture name="val_capture_options_vs_qty"}{/capture}
    {capture name="val_capture_buttons"}{/capture}
    {capture name="val_no_ajax"}{/capture}

    {script src="js/tygh/exceptions.js"}

    {$obj_prefix=$obj_prefix|default:"ajax"}
    <div class="ty-product-block" id="product_main_info_{$obj_prefix}">
        <div class="ty-product-block__wrapper clearfix">
        {$show_sku = true}
        {$show_rating = true}
        {$show_old_price = true}
        {$show_price = true}
        {$show_list_discount = true}
        {$show_clean_price = true}
        {$show_product_labels = true}
        {$show_discount_label = true}
        {$show_shipping_label = true}
        {$show_product_amount = true}
        {$show_product_options = true}
        {$min_qty = true}
        {$show_edp = true}
        {$show_add_to_cart = true}
        {$show_list_buttons = true}
        {$block_width = true}
        {$separate_buttons = true}
        {$show_descr = true}
        {$hide_form = $smarty.capture.val_hide_form}

        {hook name="products:view_main_info"}
            {if $product}

                <div class="ty-quick-view-tools">
                    {include file="common/view_tools.tpl" quick_view=true}
                </div>

                {$obj_id=$product.product_id}

                {include file="common/product_data.tpl"
                    obj_prefix=$obj_prefix
                    obj_id=$obj_id
                    product=$product
                    but_role="big"
                    but_text=__("add_to_cart")
                    add_to_cart_meta="cm-form-dialog-closer"
                    show_sku=$show_sku
                    show_rating=$show_rating
                    show_old_price=$show_old_price
                    show_price=$show_price
                    show_list_discount=$show_list_discount
                    show_clean_price=$show_clean_price
                    details_page=true
                    show_product_labels=$show_product_labels
                    show_discount_label=$show_discount_label
                    show_shipping_label=$show_shipping_label
                    show_product_amount=$show_product_amount
                    show_product_options=$show_product_options
                    hide_form=$hide_form
                    min_qty=$min_qty
                    show_edp=$show_edp
                    show_add_to_cart=$show_add_to_cart
                    show_list_buttons=$show_list_buttons
                    capture_buttons=$smarty.capture.val_capture_buttons
                    capture_options_vs_qty=$smarty.capture.val_capture_options_vs_qty
                    separate_buttons=$separate_buttons
                    block_width=$block_width
                    no_ajax=$smarty.capture.val_no_ajax
                    show_descr=$show_descr
                    quick_view=true
                }

                {assign var="form_open" value="form_open_`$obj_id`"}
                {assign var="product_detail_view_url" value="products.view?product_id=`$product.product_id`"}
                
                {$thumbnail_width = $settings.Thumbnails.product_quick_view_thumbnail_width}
                {$thumbnail_height = $settings.Thumbnails.product_quick_view_thumbnail_height}

                <div id="product_main_info_form_{$obj_prefix}">
                {$smarty.capture.$form_open nofilter}

                <div class="ty-product-block__img-wrapper" style="width:{$thumbnail_width|default:$thumbnail_height}px; max-width:{$thumbnail_width|default:$thumbnail_height}px; height: {$thumbnail_height|default:$thumbnail_width}px;">
                    {hook name="products:quick_view_image_wrap"}
                        {if !$no_images}
                            <div class="ty-product-block__img cm-reload-{$obj_prefix}{$obj_id}" data-ca-previewer="true" id="product_images_{$obj_prefix}{$obj_id}_update">
                                {assign var="product_labels" value="product_labels_`$obj_prefix``$obj_id`"}
                                {$smarty.capture.$product_labels nofilter}

                                {include file="views/products/components/product_images.tpl" product=$product show_detailed_link=true image_width=$settings.Thumbnails.product_quick_view_thumbnail_width image_height=$settings.Thumbnails.product_quick_view_thumbnail_height}
                            <!--product_images_{$obj_prefix}{$obj_id}_update--></div>
                        {/if}
                    {/hook}
                </div>

                <div class="ty-product-block__left">

                    {capture name="product_detail_view_url"}
                        {hook name="products:product_detail_view_url"}
                            {$product_detail_view_url}
                        {/hook}
                    {/capture}

                    {$product_detail_view_url = $smarty.capture.product_detail_view_url|trim}

                    {hook name="products:quick_view_title"}
                        {if !$hide_title}
                            <h1 class="ty-product-block-title">
                                <a href="{$product_detail_view_url|fn_url}" class="ty-quick-view__title" {live_edit name="product:product:{$product.product_id}"}><bdi>{$product.product nofilter}</bdi></a>
                            </h1>
                        {/if}
                    {/hook}

                    {hook name="products:brand"}
                        <div class="ty-brand">
                            {include file="views/products/components/product_features_short_list.tpl" features=$product.header_features}
                        </div>
                    {/hook}

                    {assign var="prod_descr" value="prod_descr_`$obj_id`"}
                    {if $smarty.capture.$prod_descr|trim}
                        <div class="ty-product-block__description">{$smarty.capture.$prod_descr nofilter}</div>
                    {/if}

                    <div class="ty-product-block__note">
                        {$product.promo_text nofilter}
                    </div>

                    <div class="{if $old_price && $smarty.capture.$old_price|trim || $clean_price && $smarty.capture.$clean_price|trim || $list_discount && $smarty.capture.$list_discount|trim}prices-container {/if}price-wrap clearfix">
                        {assign var="old_price" value="old_price_`$obj_id`"}
                        {assign var="price" value="price_`$obj_id`"}
                        {assign var="clean_price" value="clean_price_`$obj_id`"}
                        {assign var="list_discount" value="list_discount_`$obj_id`"}
                        {assign var="product_labels" value="product_labels_`$obj_id`"}

                         <div class="{if $smarty.capture.$old_price|trim || $smarty.capture.$clean_price|trim || $smarty.capture.$list_discount|trim}prices-container {/if}price-wrap clearfix">
                            {if $smarty.capture.$old_price|trim || $smarty.capture.$clean_price|trim || $smarty.capture.$list_discount|trim}
                                <div class="ty-float-left ty-product-prices">
                                    {if $smarty.capture.$old_price|trim}{$smarty.capture.$old_price nofilter}&nbsp;{/if}
                            {/if}

                            {hook name="products:main_price"}
                                <div class="ty-product-block__price-actual">
                                    {$smarty.capture.$price nofilter}
                                </div>
                            {/hook}

                            {if $smarty.capture.$old_price|trim || $smarty.capture.$clean_price|trim || $smarty.capture.$list_discount|trim}
                                    {$smarty.capture.$clean_price nofilter}
                                    {$smarty.capture.$list_discount nofilter}
                                </div>
                            {/if}
                        </div>

                        {if $smarty.capture.$product_labels|trim}
                            <div class="ty-float-left">
                                {$smarty.capture.$product_labels nofilter}
                            </div>
                        {/if}

                    </div>

                    {if $capture_options_vs_qty}{capture name="product_options"}{$smarty.capture.product_options nofilter}{/if}
                       <div class="ty-product-block__option">
                            {assign var="product_options" value="product_options_`$obj_id`"}
                            {$smarty.capture.$product_options nofilter}
                        </div>

                    {if $capture_options_vs_qty}{/capture}{/if}
                    <div class="ty-product-block__advanced-option clearfix">
                        {if $capture_options_vs_qty}{capture name="product_options"}{$smarty.capture.product_options nofilter}{/if}
                        {assign var="advanced_options" value="advanced_options_`$obj_id`"}
                        {$smarty.capture.$advanced_options nofilter}
                        {if $capture_options_vs_qty}{/capture}{/if}
                    </div>

                    <div class="ty-product-block__sku">
                        {$sku = "sku_`$obj_id`"}
                        {$smarty.capture.$sku nofilter}
                    </div>

                    {if $capture_options_vs_qty}{capture name="product_options"}{$smarty.capture.product_options nofilter}{/if}
                    <div class="ty-product-block__field-group">
                        {assign var="product_amount" value="product_amount_`$obj_id`"}
                        {$smarty.capture.$product_amount nofilter}

                        {assign var="qty" value="qty_`$obj_id`"}
                        {$smarty.capture.$qty nofilter}

                        {assign var="min_qty" value="min_qty_`$obj_id`"}
                        {$smarty.capture.$min_qty nofilter}
                    </div>
                    {if $capture_options_vs_qty}{/capture}{/if}
                    {assign var="product_edp" value="product_edp_`$obj_id`"}
                    {$smarty.capture.$product_edp nofilter}

                    {if $capture_buttons}{capture name="buttons"}{/if}
                    <div class="ty-product-block__button">
                            {assign var="add_to_cart" value="add_to_cart_`$obj_id`"}
                            {$smarty.capture.$add_to_cart nofilter}

                            {assign var="list_buttons" value="list_buttons_`$obj_id`"}
                            {$smarty.capture.$list_buttons nofilter}
                    </div>
                    {if $capture_buttons}{/capture}{/if}
                </div>
                {if $obj_prefix === "ajax"}
                    <input type="hidden" class="cm-no-hide-input" name="security_hash" value="{""|fn_generate_security_hash}">
                {/if}
                {assign var="form_close" value="form_close_`$obj_id`"}
                {$smarty.capture.$form_close nofilter}
                <!--product_main_info_form_{$obj_prefix}--></div>
            {/if}
        {/hook}
        </div>

        {if $smarty.capture.hide_form_changed == "Y"}
            {assign var="hide_form" value=$smarty.capture.orig_val_hide_form}
        {/if}
    <!--product_main_info_{$obj_prefix}--></div>
</div>
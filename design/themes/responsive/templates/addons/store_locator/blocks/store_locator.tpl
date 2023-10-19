{** block-description:store_locator **}
<form action="{""|fn_url}" method="get" name="store_locator_form">
    <div id="store_locator_search_block_{$block.block_id}">
        <div class="ty-control-group">
            <label for="store_locator_search_city_{$block.block_id}" class="ty-control-group__title">{__("storefront_search_label")}</label>

            <div class="ty-input-append ty-m-none">
                <input type="text" size="20" class="ty-input-text" id="store_locator_search_city_{$block.block_id}" name="sl_search[q]" value="{$sl_search.q}" />
                {include file="buttons/go.tpl" but_name="store_locator.search" alt=__("storefront_search_button")}
            </div>
        </div>
    <!--store_locator_search_block_{$block.block_id}--></div>
</form>
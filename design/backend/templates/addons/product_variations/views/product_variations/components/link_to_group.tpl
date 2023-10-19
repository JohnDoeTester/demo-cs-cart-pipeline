<div class="object-picker__simple object-picker__simple--variation-group input-xlarge">
    <input type="hidden" value="{$product_id}" name="product_id">
    <select id="product_variations_code"
        class="cm-object-picker object-picker__select product-variations__toolbar-code-link"
        name="group_id"
        data-ca-object-picker-placeholder="{__("product_variations.group_code.link")}"
    >
        <option value="">-{__("none")}-</option>
        {foreach $group_codes as $group_id => $group_code}
            <option value="{$group_id}">{$group_code}</option>
        {/foreach}
        <option value="">-{__("none")}-</option>
    </select>
</div>
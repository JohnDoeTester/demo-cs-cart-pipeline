<div class="object-picker__products-position">
    <input type="text"
        name="{$input_name}[{literal}${data.product_id}{/literal}]"
        value="{literal}${(data._index + 1) * 10}{/literal}"
        size="3"
        class="input-micro"
        placeholder="{__("position_short")}"
    />
</div>
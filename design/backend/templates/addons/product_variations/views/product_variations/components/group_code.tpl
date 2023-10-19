<input type="hidden" name="variation_group[id]" value="{$group->getId()}" />
<div class="input-prepend shift-left product-variations__toolbar-code-wrapper">
    <span class="add-on product-variations__toolbar-code-addon">{__("product_variations.group_code")}{include file="common/tooltip.tpl" tooltip=__("product_variations.group_code.description")}</span>
    <input class="product-variations__toolbar-code" id="prependedInput" type="text" name="variation_group[code]" data-ca-meta-class="product-variations__toolbar-code product-variations__toolbar-code--text" placeholder="{__("product_variations.group_code.placeholder")}" value="{$group->getCode()}">
</div>
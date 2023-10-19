<div class="litecheckout__group">
    <div class="litecheckout__field litecheckout__field--medium">
        <input id="elm_po_number" size="35" type="text" name="payment_info[po_number]" value="{$cart.payment_info.po_number}" class="ty-input-text cm-focus litecheckout__input" placeholder=" "/>
        <label for="elm_po_number" class="cm-required litecheckout__label">{__("po_number")}:</label>
    </div>

    <div class="litecheckout__field litecheckout__field--medium">
        <input id="elm_company_name" size="35" type="text" name="payment_info[company_name]" value="{$cart.payment_info.company_name}" class="ty-input-text litecheckout__input" placeholder=" "/>
        <label for="elm_company_name" class="cm-required litecheckout__label">{__("company_name")}:</label>
    </div>

    <div class="litecheckout__field litecheckout__field--medium">
        <input id="elm_buyer_name" size="35" type="text" name="payment_info[buyer_name]" value="{$cart.payment_info.buyer_name}" class="ty-input-text litecheckout__input" placeholder=" "/>
        <label for="elm_buyer_name" class="cm-required litecheckout__label">{__("buyer_name")}:</label>
    </div>

    <div class="litecheckout__field litecheckout__field--medium">
        <input id="elm_position" size="35" type="text" name="payment_info[position]" value="{$cart.payment_info.position}" class="ty-input-text-short litecheckout__input" placeholder=" "/>
        <label for="elm_position" class="cm-required litecheckout__label">{__("position")}:</label>
    </div>
</div>

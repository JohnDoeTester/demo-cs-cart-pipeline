<div class="control-group">
    <label for="elm_name" class="cm-required control-label">{__("warehouses.store_type")}:</label>
    <div class="controls">
        <input type="hidden" name="store_location_data[store_type]" value="P">
        <select name="store_location_data[store_type]" id="store_type_{$id}">
            {$store_type = $store_location.store_type|default:$smarty.request.store_type}
            {foreach $store_types as $type_code => $type_name}
                <option value="{$type_code}"{if $type_code == $store_type} selected{/if}>{$type_name}</option>
            {/foreach}
        </select>
    </div>
</div>
<script>
    (function(_, $) {
        var $selectedCompanyId = $('#company_id_{$id}');
        var $storeType = $('#store_type_{$id}');

        $.ceEvent('on', 'ce.commoninit', function () {
            if ($selectedCompanyId.val() == 0) {
                $storeType.prop('disabled', true);
                $storeType.val('P');
            } else {
                $storeType.prop('disabled', null);
            }
        });

        $selectedCompanyId.change(function () {
            if ($selectedCompanyId.val() == 0) {
                $storeType.prop('disabled', true);
                $storeType.val('P');
            } else {
                $storeType.prop('disabled', null);
            }
        });
    })(Tygh, Tygh.$);
</script>


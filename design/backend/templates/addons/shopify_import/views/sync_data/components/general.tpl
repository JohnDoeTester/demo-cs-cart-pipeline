<script>
    (function(_, $) {
        $(document).ready(function() {
            $('#shopify_import_company_id_container').switchAvailability(false, false);

            $(_.doc).on('click', 'input[name="sync_data_settings[{$sync_provider_id}][general.import_mode]"]', function() {
                $('#shopify_import_company_id_container').switchAvailability($(this).val() === 'all_vendors', true);
            });
        });
    }(Tygh, Tygh.$));
</script>

<div class="control-group">
    <label class="control-label">{__("select_file")}:</label>
    <div class="controls">
        {include file="common/fileuploader.tpl" var_name="csv_file[0]" prefix="shopify_import" hide_server=true allowed_ext="csv"}
    </div>
</div>

{if "MULTIVENDOR"|fn_allowed_for && !$runtime.company_id}
    <div class="control-group">
        <label class="control-label">{__("shopify_import.choose_import_mode")}:</label>

        <div class="controls">
            <label class="radio">
                <input type="radio" value="all_vendors" name="sync_data_settings[{$sync_provider_id}][general.import_mode]" {if $addons.master_products.status !== "ObjectStatuses::ACTIVE"|enum}disabled{else} checked="checked"{/if}>
                {__("shopify_import.import_for_all_vendors")}
                {if $addons.master_products.status !== "ObjectStatuses::ACTIVE"|enum}<p class="muted">{__("shopify_import.depends_on_common_products_addon", ["[link]" => "addons.update?addon=master_products"|fn_url])}</p>{/if}
            </label>
            <label class="radio">
                <input type="radio" value="specific_vendor" name="sync_data_settings[{$sync_provider_id}][general.import_mode]" {if $addons.master_products.status !== "ObjectStatuses::ACTIVE"|enum}checked="checked"{/if}>
                {__("shopify_import.import_for_specific_vendor")}
            </label>
        </div>

        <div id="shopify_import_company_id_container" {if $addons.master_products.status === "ObjectStatuses::ACTIVE"|enum}class="hidden"{/if}>
            {include file="views/companies/components/company_field.tpl"
                name="sync_data_settings[{$sync_provider_id}][general.company_id]"
                id="shopify_import_company_id"
                company_field_name=__("vendor")
            }
        </div>
    </div>
{/if}

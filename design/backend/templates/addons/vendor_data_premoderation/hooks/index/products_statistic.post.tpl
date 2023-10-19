{if isset($vendor_data_premoderation.require_approval_count)}
<div class="dashboard-card">
    <div class="dashboard-card-title">
        {__("vendor_data_premoderation.require_approval")}
    </div>
    <div class="dashboard-card-content">
        <h3>
            <a href="{fn_url("products.manage?status={"Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum}")}">
                {$vendor_data_premoderation.require_approval_count|number_format}
            </a>
        </h3>
    </div>
</div>
{/if}

{if isset($vendor_data_premoderation.disapproved_count)}
<div class="dashboard-card">
    <div class="dashboard-card-title">{__("vendor_data_premoderation.disapproved")}</div>
    <div class="dashboard-card-content">
        <h3>
            <a href="{fn_url("products.manage?status={"Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum}")}">
                {$vendor_data_premoderation.disapproved_count|number_format}
            </a>
        </h3>
    </div>
</div>
{/if}

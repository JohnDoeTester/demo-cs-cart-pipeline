{include file="common/switcher.tpl"
    meta = "company-switch-storefront-status-button"
    id = "switch_storefront_status_`$company.company_id`"
    checked = $company.storefront_status == "StorefrontStatuses::OPEN"|enum
    extra_attrs=["data-ca-company-id" => {$company.company_id}, "data-ca-opened-status" => {"StorefrontStatuses::OPEN"|enum}, "data-ca-closed-status" => {"StorefrontStatuses::CLOSED"|enum}, "data-ca-return-url" => {$config.current_url|escape:url}]
}

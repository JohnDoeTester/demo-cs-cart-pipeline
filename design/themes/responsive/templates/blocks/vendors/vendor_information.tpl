{** block-description:block_vendor_information **}

<div class="ty-vendor-information">
    <span><a href="{"companies.view?company_id=`$vendor_info.company_id`"|fn_url}">{$vendor_info.company}</a></span>
    <div class="ty-wysiwyg-content">{$vendor_info.company_description nofilter}</div>
</div>
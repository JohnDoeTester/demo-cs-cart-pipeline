{hook name="suppliers:view"}

{$obj_id=$supplier.company_id}
{include file="common/company_data.tpl" company=$supplier show_name=true show_descr=true show_rating=true show_logo=true show_links=false show_address=true show_location_full=true}

<div class="ty-company-detail clearfix">

    <div id="block_company_{$supplier.supplier_id}">
        <h1 class="ty-mainbox-title">{$supplier.name}</h1>
        <div class="ty-company-detail__top-links clearfix">
            {hook name="suppliers:top_links"}
                <div id="company_products">
                    <a href="{"products.search?supplier_id=`$supplier.supplier_id`&search_performed=Y"|fn_url}">{__("view_supplier_products")} ({$supplier.products|count} {__("items")})</a>
                </div>
            {/hook}
        </div>
        <div class="ty-company-detail__info company-page-info">
            <div class="ty-company-detail__info-list ty-company-detail_info-first">
                <h5 class="ty-company-detail__info-title">{__("contact_information")}</h5>
                {if $supplier.email}
                    <div class="ty-company-detail__control-group" id="supplier_email">
                        <label class="ty-company-detail__control-label">{__("email")}:</label>
                        <span><a href="mailto:{$supplier.email}">{$supplier.email}</a></span>
                    </div>
                {/if}
                {if $supplier.phone}
                    <div class="ty-company-detail__control-group" id="supplier_phone">
                        <label class="ty-company-detail__control-label">{__("phone")}:</label>
                        <span>{$supplier.phone}</span>
                    </div>
                {/if}
                {if $supplier.fax}
                    <div class="ty-company-detail__control-group" id="supplier_phone">
                        <label class="ty-company-detail__control-label">{__("fax")}:</label>
                        <span>{$supplier.fax}</span>
                    </div>
                {/if}
                {if $supplier.url}
                    <div class="ty-company-detail__control-group" id="supplier_website">
                        <label>{__("website")}:</label>
                        <span><a href="{$supplier.url}">{$supplier.url}</a></span>
                    </div>
                {/if}
            </div>
            <div class="ty-company-detail__info-list">
                <h5 class="ty-company-detail__info-title">{__("shipping_address")}</h5>
                {$address="address_`$obj_id`"}
                {if $smarty.capture.$address|trim}
                    <div class="ty-company-detail__control-group">
                        <span>{$smarty.capture.$address nofilter}</span>
                    </div>
                {/if}

                {$location_full="location_full_`$obj_id`"}
                {if $smarty.capture.$location_full|trim}
                    <div class="ty-company-detail__control-group">
                        <span>{$smarty.capture.$location_full nofilter}</span>
                    </div>
                {/if}
                <div class="ty-company-detail__control-group">
                    <span>{$supplier.country|fn_get_country_name}</span>
                </div>
            </div>
        </div>

    </div>

</div>

{capture name="tabsbox"}

{hook name="suppliers:tabs"}
{/hook}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section}

{/hook}

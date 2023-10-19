{*
    $product_reviews_search         array                               Product reviews search
    $available_message_types        array                               Available message types
    $message_type                   string                              Message type
    $company_id                     int                                 Company ID
*}

<div class="sidebar-row">
    <h6>{__("admin_search_title")}</h6>

    <form action="{""|fn_url}" name="sidebar_review_search_form" method="get">
        <input type="hidden" name="dispatch" value="product_reviews.manage"/>

        {capture name="simple_search"}
            <div class="sidebar-field">
                <label for="customer">{__("customer")}</label>
                <input type="text" id="customer" name="name" value="{$product_reviews_search.name}"/>
            </div>

            {foreach $available_message_types as $message_type}
                <div class="sidebar-field">
                    <label for="{$message_type}">{__("product_reviews.$message_type")}</label>
                    <input type="text" id="{$message_type}" name="{$message_type}" value="{$product_reviews_search.$message_type}"/>
                </div>
            {/foreach}
            
            <div class="sidebar-field">
                <label for="rating_value">{__("product_reviews.rating")}</label>
                <select name="rating" id="rating_value">
                <option value="">--</option>
                    <option value="5" {if $product_reviews_search.rating === "5"}selected="selected"{/if}>{__("product_reviews.five_star_icon")}</option>
                    <option value="4" {if $product_reviews_search.rating === "4"}selected="selected"{/if}>{__("product_reviews.four_star_icon")}</option>
                    <option value="3" {if $product_reviews_search.rating === "3"}selected="selected"{/if}>{__("product_reviews.three_star_icon")}</option>
                    <option value="2" {if $product_reviews_search.rating === "2"}selected="selected"{/if}>{__("product_reviews.two_star_icon")}</option>
                    <option value="1" {if $product_reviews_search.rating === "1"}selected="selected"{/if}>{__("product_reviews.one_star_icon")}</option>
                </select>
            </div>

            <div class="sidebar-field">
                <label for="helpfulness_from">{__("product_reviews.helpfulness")}</label>
                <input type="text" name="helpfulness_from" id="helpfulness_from" value="{$product_reviews_search.helpfulness_from}" onfocus="this.select();" class="input-small" />
                -
                <input type="text" name="helpfulness_to" value="{$product_reviews_search.helpfulness_to}" onfocus="this.select();" class="input-small" />
            </div>

            <div class="sidebar-field">
                <label for="product_reviews">{__("product_reviews.with_photo")}</label>
                <select name="has_images" id="with_photo">
                    <option value="">--</option>
                    <option value="1" {if $product_reviews_search.has_images}selected="selected"{/if}>{__("product_reviews.with_photo")}</option>
                    <option value="0" {if $product_reviews_search.has_images === "0"}selected="selected"{/if}>{__("product_reviews.without_photo")}</option>
                </select>
            </div>

            {if "MULTIVENDOR"|fn_allowed_for && !$runtime.company_id}
                <div class="sidebar-field">
                    <label for="product_reviews_type">{__("vendor")}</label>
                    {include file="views/companies/components/picker/picker.tpl"
                        input_name="company_id"
                        show_advanced=false
                        show_empty_variant=true
                        item_ids=($product_reviews_search.company_id) ? [$product_reviews_search.company_id] : []
                        empty_variant_text=__("any_vendor")
                    }
                </div>
            {/if}
        {/capture}

        {capture name="advanced_search"}
            <div class="group form-horizontal">
                <div class="control-group">
                <label class="control-label">{__("period")}</label>
                <div class="controls">
                    {include file="common/period_selector.tpl" period=$product_reviews_search.period form_name="sidebar_review_search_form" search=$product_reviews_search}
                </div>
            </div>

            <div class="group form-horizontal">
                <div class="control-group">
                    <label class='control-label' for="ip_address">{__("ip_address")}</label>
                    <div class="controls">
                        <input type="text" id="ip_address" name="ip_address" value="{$product_reviews_search.ip_address}" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="status">{__("product_reviews.approved")}</label>
                    <div class="controls">
                        <select name="status" id="status">
                            <option value="">--</option>
                            <option value="A" {if $product_reviews_search.status === "A"}selected="selected"{/if}>{__("product_reviews.approved")}</option>
                            <option value="D" {if $product_reviews_search.status === "D"}selected="selected"{/if}>{__("product_reviews.not_approved")}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="group form-horizontal">
                <div class="control-group">
                    <label class="control-label" for="sort_by">{__("sort_by")}</label>
                    <div class="controls">
                        <select name="sort_by" id="sort_by" class="input-small">
                            <option {if $product_reviews_search.sort_by === "rating_value"}selected="selected"{/if} value="rating_value">{__("product_reviews.rating")}</option>
                            <option {if $product_reviews_search.sort_by === "helpfulness"}selected="selected"{/if} value="helpfulness">{__("product_reviews.helpfulness")}</option>
                            <option {if $product_reviews_search.sort_by === "product_review_timestamp"}selected="selected"{/if} value="product_review_timestamp">{__("date")}</option>
                        </select>

                        <select name="sort_order" class="input-small">
                            <option {if $product_reviews_search.sort_order === "desc"}selected="selected"{/if} value="desc">{__("desc")}</option>
                            <option {if $product_reviews_search.sort_order === "asc"}selected="selected"{/if} value="asc">{__("asc")}</option>
                        </select>
                    </div>
                </div>
            </div>

        {/capture}

        {include file="common/advanced_search.tpl"
            simple_search=$smarty.capture.simple_search
            advanced_search=$smarty.capture.advanced_search
            dispatch="product_reviews.manage"
            view_type="product_reviews"
            not_saved=true
        }

    </form>

</div>
{capture name="extra"}
    {$enable_search = $enable_search|default:false}

    <div class="pull-left">
        {if !$enable_search}
        <div class="object-selector object-selector--options">
            <select id="option_add"
                    class="cm-object-selector"
                    form="form"
                    {if $tabindex}
                        tabindex="{$tabindex}"
                    {/if}
                    multiple
                    name="product_data[linked_option_ids][]"
                    data-ca-enable-search="true"
                    data-ca-load-via-ajax="true"
                    data-ca-escape-html="false"
                    data-ca-close-on-select="false"
                    data-ca-page-size="10"
                    data-ca-data-url="{"product_options.get_available_options_list?product_id=`$smarty.request.product_id`"|fn_url nofilter}"
                    data-ca-placeholder="{__("link_an_existing_option")}"
                    data-ca-allow-clear="false"
            >
            </select>
        </div>
        {/if}

        {if $product_options}
            {hook name="products:update_product_options_actions"}

            {if $product_data.exceptions_type === "ProductOptionsExceptionsTypes::FORBIDDEN"|enum}
                {assign var="except_title" value=__("forbidden_combinations")}
            {else}
                {assign var="except_title" value=__("allowed_combinations")}
            {/if}
            {include file="buttons/button.tpl" but_text=$except_title but_href="product_options.exceptions?product_id=`$product_data.product_id`" but_meta="btn" but_role="text"}

            {/hook}
        {/if}
    </div>
{/capture}

{include file="views/product_options/manage.tpl" object="product" extra=$smarty.capture.extra product_id=$smarty.request.product_id view_mode="embed" enable_search=$enable_search}

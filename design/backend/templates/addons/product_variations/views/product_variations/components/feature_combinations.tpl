{if $combinations}
    <form action="{"product_variations.generate"|fn_url}" name="generate_product_to_group_form" method="post">
        <input type="hidden" name="product_id" value="{$product_data.product_id}" />

        <div class="items-container">
            {$first_combination = $combinations|reset}
            {$levels_count = $first_combination.selected_variants|count}

            {if $levels_count > 1}
                {include file="addons/product_variations/views/product_variations/components/feature_combinations_grouped_list.tpl"
                    combinations=$combinations
                }
            {else}
                {include file="addons/product_variations/views/product_variations/components/feature_combinations_list.tpl"
                    combinations=$combinations
                }
            {/if}
        </div>
    </form>
{elseif $is_too_many_combinations}
    <div class="no-items row-fluid">
        <div class="span8 offset2 left">{__("product_variations.too_many_combinations")}</div>
    </div>
{else}
    <div class="no-items row-fluid">
        <div class="span8 offset2 left">{__("product_variations.no_available_features", ["[manage_features_href]" => "product_features.manage"|fn_url]) nofilter}</div>
    </div>
{/if}

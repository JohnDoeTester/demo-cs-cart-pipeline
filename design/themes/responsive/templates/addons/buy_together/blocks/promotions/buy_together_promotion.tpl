{** block-description:buy_together **}

{script src="js/tygh/exceptions.js"}

{if $chains}

    {if !$config.tweaks.disable_dhtml && !$no_ajax}
        {assign var="is_ajax" value=true}
    {/if}
    
    {foreach from=$chains key="key" item="chain"}
        <div class="ty-column3">
            <div class="ty-grid-list__item ty-grid-promotions__item">
                {if $chain.main_pair}
                    {include file="common/image.tpl"
                        images=$chain.main_pair
                        image_id="`$chain.chain_id`_`$chain.product_id`"
                        class="ty-grid-promotions__image"
                        image_width=$promotion_image_width|default:''
                        image_height=$promotion_image_height|default:''
                    }
                {/if}

                <div class="ty-grid-promotions__content">
                    <a class="cm-dialog-opener cm-dialog-auto-size" data-ca-target-id="сontent_by_together_promotions_{$chain.chain_id}">
                        <h2 class="ty-buy-together__header ty-grid-promotions__header">{$chain.name}</h2>
                    </a>

                    {if $chain.date_to}
                        <div class="ty-grid-list__available">
                            {__("avail_till")}: {$chain.date_to|date_format:$settings.Appearance.date_format}
                        </div>
                    {/if}

                    {if "MULTIVENDOR"|fn_allowed_for && ($company_name || $chain.company_id)}
                        <div class="ty-grid-promotions__company">
                            <a href="{"companies.products?company_id=`$promotion.company_id`"|fn_url}" class="ty-grid-promotions__company-link">
                                {if $company_name}{$company_name}{else}{$chain.company_id|fn_get_company_name}{/if}
                            </a>
                        </div>
                    {/if}
                
                    {if $chain.description}
                        <div class="ty-wysiwyg-content ty-grid-promotions__description">
                            {$chain.description nofilter}
                        </div>
                    {/if}
                </div>
            </div>
        </div>

        <div class="hidden" id="сontent_by_together_promotions_{$chain.chain_id}" title="{$chain.name}">
            {include file="addons/buy_together/components/buy_together_chain_form.tpl"}
        </div>
    {/foreach}
    
{/if}

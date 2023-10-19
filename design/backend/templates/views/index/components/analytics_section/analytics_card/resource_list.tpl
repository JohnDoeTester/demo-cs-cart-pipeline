{if $resource_list && ($resource_list.content || empty($resource_list.content))}{strip}
    {*
        $analytics_card
        $resource_list
        $item
        $settings
    *}
    <div class="analytics-card-resource-list">
        {if $resource_list.title}
            <div class="analytics-card-resource-list__title">{$resource_list.title nofilter}</div>
        {/if}
        <div class="analytics-card-resource-list__content">
            {foreach $resource_list.content as $item}
                <div class="analytics-card-resource-list__item {""}
                    {if $item.description || $item.description_href || $item.small_text}
                        analytics-card-resource-list__item--divider
                    {/if}
                ">
                    {if $item.image}
                        <div class="analytics-card-resource-list__image">
                            {include file="common/image.tpl"
                                image=$item.image
                                image_id="`$analytics_card.id`_`$item.id`"
                                image_width=$settings.Thumbnails.product_admin_mini_icon_width
                                image_height=$settings.Thumbnails.product_admin_mini_icon_height
                                href=$item.href|fn_url
                            }
                        </div>
                    {/if}
                    <div class="analytics-card-resource-list__item-content">
                        {if $item.name || $item.href || $item.value || $item.value_href}
                            <div class="analytics-card-resource-list__primary">
                                <div class="analytics-card-resource-list__name">
                                    {if $item.href}
                                        <a href="{$item.href|fn_url}"
                                            class="analytics-card-resource-list__name-text analytics-card-resource-list__name-text--link"
                                        >
                                            {$item.name nofilter}
                                        </a>
                                    {else}
                                        <div class="analytics-card-resource-list__name-text">
                                            {$item.name nofilter}
                                        </div>
                                    {/if}
                                    {if $item.label_text}
                                        <div class="analytics-card-resource-list__label label btn-info label--text-wrap {$item.label_class}">
                                            {$item.label_text}
                                        </div>
                                    {/if}
                                </div>
                                {if $item.value_href && isset($item.value)}
                                    <a href="{$item.value_href|fn_url}"
                                        class="analytics-card-resource-list__value analytics-card-resource-list__value--link"
                                    >
                                        {$item.value nofilter}
                                    </a>
                                {elseif isset($item.value)}
                                    <div class="analytics-card-resource-list__value">
                                        {$item.value nofilter}
                                    </div>
                                {/if}
                            </div>
                        {/if}
                        {if $item.description || $item.description_href || $item.small_text}
                            <div class="analytics-card-resource-list__secondary">
                                {if $item.description_href}
                                    <a href="{$item.description_href|fn_url}"
                                        class="analytics-card-resource-list__description analytics-card-resource-list__description--link"
                                    >
                                        {$item.description nofilter}
                                    </a>
                                {else}
                                    <div class="analytics-card-resource-list__description">
                                        {$item.description nofilter}
                                    </div>
                                {/if}
                                {if $item.small_text}
                                    <div class="analytics-card-resource-list__small-text">
                                        {$item.small_text nofilter}
                                    </div>
                                {/if}
                            </div>
                        {/if}
                    </div>
                </div>
            {foreachelse}
                <div class="analytics-card-resource-list__item analytics-card-resource-list__item--empty">
                    <div class="analytics-card-resource-list__no-items no-items">
                        {__("no_data")}
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
{/strip}{/if}
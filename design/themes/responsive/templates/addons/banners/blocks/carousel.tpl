{** block-description:carousel **}

{if $items}
    <div id="banner_slider_{$block.snapping_id}" class="banners owl-carousel ty-scroller"
        data-ca-scroller-item="1"
        data-ca-scroller-item-desktop="1"
        data-ca-scroller-item-desktop-small="1"
        data-ca-scroller-item-tablet="1"
        data-ca-scroller-item-mobile="1"
    >
        {foreach from=$items item="banner" key="key"}
            <div class="ty-banner__image-item ty-scroller__item">
                {if $banner.type == "G" && $banner.main_pair.image_id}
                    {if $banner.url != ""}<a class="banner__link" href="{$banner.url|fn_url}" {if $banner.target == "B"}target="_blank"{/if}>{/if}
                        {include 
                            file="common/image.tpl" 
                            images=$banner.main_pair 
                            class="ty-banner__image"
                            image_width=$block.content.width
                            image_height=$block.content.height
                        }
                    {if $banner.url != ""}</a>{/if}
                {else}
                    <div class="ty-wysiwyg-content">
                        {$banner.description nofilter}
                    </div>
                {/if}
            </div>
        {/foreach}
    </div>
{/if}

<script>
(function(_, $) {
    $.ceEvent('on', 'ce.commoninit', function(context) {
        var slider = context.find('#banner_slider_{$block.snapping_id}');
        if (slider.length) {
            slider.owlCarousel({
                direction: '{$language_direction}',
                items: 1,
                singleItem : true,
                slideSpeed: {$block.properties.speed|default:400},
                autoPlay: {($block.properties.delay > 0) ? $block.properties.delay * 1000 : "false"},
                stopOnHover: true,
                beforeInit: function () {
                    $.ceEvent('trigger', 'ce.banner.carousel.beforeInit', [this]);
                },
                {if $block.properties.navigation == "N"}
                    pagination: false
                {/if}
                {if $block.properties.navigation == "D"}
                    pagination: true
                {/if}
                {if $block.properties.navigation == "P"}
                    pagination: true,
                    paginationNumbers: true
                {/if}
                {if $block.properties.navigation == "A"}
                    pagination: false,
                    navigation: true,
                    navigationText: ['{__("prev_page")}', '{__("next")}']
                {/if}
            });
        }
    });
}(Tygh, Tygh.$));
</script>

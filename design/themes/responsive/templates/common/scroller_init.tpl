<script>
(function(_, $) {
    $.ceEvent('on', 'ce.commoninit', function(context) {
        var elm = context.find('#scroll_list_{$block.block_id}');

        $('.ty-float-left:contains(.ty-scroller-list),.ty-float-right:contains(.ty-scroller-list)').css('width', '100%');

        var item = {$block.properties.item_quantity|default:5},
            // default setting of carousel
            itemsDesktop = 4,
            itemsDesktopSmall = 3,
            itemsTablet = 2,
            itemsMobile = {$item_quantity_responsive["mobile"]|default:1};

        if (item > 3) {
            itemsDesktop = item;
            itemsDesktopSmall = item - 1;
            itemsTablet = item - 2;
        } else if (item == 1) {
            itemsDesktop = itemsDesktopSmall = itemsTablet = 1;
        } else {
            itemsDesktop = item;
            itemsDesktopSmall = itemsTablet = item - 1;
        }

        var desktop = [1199, itemsDesktop],
            desktopSmall = [979, itemsDesktopSmall],
            tablet = [768, itemsTablet],
            mobile = [479, itemsMobile];

        {if $block.properties.outside_navigation == "Y"}
        function outsideNav () {
            if(this.options.items >= this.itemsAmount){
                $("#owl_outside_nav_{$block.block_id}").hide();
            } else {
                $("#owl_outside_nav_{$block.block_id}").show();
            }
        }
        function afterInit () {
            outsideNav.apply(this);
            $.ceEvent('trigger', 'ce.scroller.afterInit', [this]);
        }
        function afterUpdate () {
            outsideNav.apply(this);
            $.ceEvent('trigger', 'ce.scroller.afterUpdate', [this]);
        }
        {else}
        function afterInit () {
            $.ceEvent('trigger', 'ce.scroller.afterInit', [this]);
        }
        function afterUpdate () {
            $.ceEvent('trigger', 'ce.scroller.afterUpdate', [this]);
        }
        {/if}
        function beforeInit () {
            $.ceEvent('trigger', 'ce.scroller.beforeInit', [this]);
        }
        function beforeUpdate () {
            $.ceEvent('trigger', 'ce.scroller.beforeUpdate', [this]);
        }

        if (elm.length) {
            elm.owlCarousel({
                direction: '{$language_direction}',
                items: item,
                itemsDesktop: desktop,
                itemsDesktopSmall: desktopSmall,
                itemsTablet: tablet,
                itemsMobile: mobile,
                {if $block.properties.scroll_per_page == "Y"}
                scrollPerPage: true,
                {/if}
                {if $block.properties.not_scroll_automatically == "Y"}
                autoPlay: false,
                {else}
                autoPlay: '{$block.properties.pause_delay * 1000|default:0}',
                {/if}
                lazyLoad: true,
                slideSpeed: {$block.properties.speed|default:400},
                stopOnHover: true,
                {if $block.properties.outside_navigation == "N"}
                navigation: true,
                navigationText: ['{__("prev_page")}', '{__("next")}'],
                {/if}
                pagination: false,
                beforeInit: beforeInit,
                afterInit: afterInit,
                beforeUpdate: beforeUpdate,
                afterUpdate: afterUpdate
            });
            {if $block.properties.outside_navigation == "Y"}

              $('{$prev_selector}').click(function(){
                elm.trigger('owl.prev');
              });
              $('{$next_selector}').click(function(){
                elm.trigger('owl.next');
              });
            {/if}

        }
    });
}(Tygh, Tygh.$));
</script>

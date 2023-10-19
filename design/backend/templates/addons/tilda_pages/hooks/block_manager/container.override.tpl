{if $is_container_override && $container.position === "ContainerPositions::CONTENT"|enum}
    <div class="device-specific-block container container_{$container.width} container-lock"
        data-ca-status="{if $container.status != "A"}disabled{else}active{/if}"
        {include file="views/block_manager/components/device_availability_attributes.tpl" item=$container}
        id="container_{$container.container_id}"
    >
        <p class="grid-control-title">
            {__("tilda_pages.tilda_override_block")}
        </p>
        
        <div class="clearfix"></div>
        <div class="grid-control-menu bm-control-menu">
            {include file="views/block_manager/components/device_icons.tpl"
                item=$container
                wrapper_class="pull-right"
            }

            <h4 class="grid-control-title">
                {__($container.position)}
            </h4>
        </div>
    <!--container_{$container.container_id}--></div>
{/if}
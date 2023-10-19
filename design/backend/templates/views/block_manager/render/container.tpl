{hook name="block_manager:container"}
    <div class="device-specific-block container container_{$container.width} {if $container.uses_default_content}container-lock{/if} {if $container.status != "A"}container-off{/if}"
        data-ca-status="{if $container.status != "A"}disabled{else}active{/if}"
        {include file="views/block_manager/components/device_availability_attributes.tpl" item=$container}
        id="container_{$container.container_id}"
    >
        {if $container.linked_message}
            <p>
                {$container.linked_message}
                <a class="cm-post" href="{$container.set_custom_config_url}">{__("set_custom_configuration")}</a>
            </p>
        {/if}

        {if $container.has_displayable_content}
            {$content nofilter}
        {/if}
        
        <div class="clearfix"></div>
        <div class="grid-control-menu bm-control-menu">
            {include file="views/block_manager/components/device_icons.tpl"
                item=$container
                wrapper_class="pull-right"
            }

            <h4 class="grid-control-title">
                {__($container.position)}
                {if $container.can_be_reset_to_default}
                    <a class="cm-post" href="{$container.set_default_config_url}">{__("use_default_block_configuration")}</a>
                {/if}
            </h4>

            {if $container.has_displayable_content && !$dynamic_object}
                <div class="grid-control-menu-actions">
                    <div class="btn-group action">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="icon-plus cm-tooltip" data-ce-tooltip-position="top" title="{__("insert_grid")}"></span></a>
                        <ul class="dropdown-menu droptop">
                            <li><a href="#" class="cm-action bm-action-add-grid">{__("insert_grid")}</a></li>
                        </ul>
                    </div>
                    <div class="cm-tooltip cm-action icon-cog bm-action-properties action" data-ce-tooltip-position="top" title="{__("container_options")}"></div>
                    <div class="cm-action bm-action-switch cm-tooltip icon-off action" data-ce-tooltip-position="top" title="{__("enable_or_disable_container")}"></div>
                </div>
            {/if}
        </div>
    <!--container_{$container.container_id}--></div>
{/hook}

<hr />

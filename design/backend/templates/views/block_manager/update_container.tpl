
{if $container}
    {assign var="id" value=$container.container_id}
{else}
    {assign var="id" value=0}
{/if}

<div id="container_properties_{$id}">
<form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="form-horizontal form-edit " name="container_update_form">
<input type="hidden" id="s_layout" name="s_layout" value="{$location.layout_id}" />
<input type="hidden" name="container_data[container_id]" value="{$id}" />

<div class="tabs cm-j-tabs">
    <ul class="nav nav-tabs">
        <li class="cm-js active"><a>{__("general")}</a></li>
    </ul>
</div>

<div class="cm-tabs-content">
<fieldset>
    <div class="control-group cm-no-hide-input">
        <label class="control-label" for="elm_container_user_class_{$id}">{__("user_class")}</label>
        <div class="controls">
        <input class="input-text" type="text" id="elm_container_user_class_{$id}" name="container_data[user_class]" value="{$container.user_class}"/>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required cm-multiple-checkboxes"
               for="container_{$id}_availability"
        >{__("block_manager.availability.show_on")}</label>
        <div class="controls" id="container_{$id}_availability">
            <div class="btn-group btn-group-checkbox">
                {foreach $container.availability as $device => $is_available}

                    {if $device == "phone"}
                        {$devices_icon = "icon-mobile-phone"}
                    {elseif $device == "tablet"}
                        {$devices_icon = "icon-tablet"}
                    {elseif $device == "desktop"}
                        {$devices_icon = "icon-desktop"}
                    {/if}

                    <input type="checkbox"
                        id="elm_container_{$id}_show_on_{$device}"
                        class="cm-text-toggle btn-group-checkbox__checkbox"
                        {if $is_available}checked="checked"{/if}
                        data-ca-toggle-text="{$container_availability_instance->getHiddenClass($device)}"
                        data-ca-toggle-text-mode="onDisable"
                        data-ca-toggle-text-target-elem-id="elm_container_user_class_{$id}"
                    />
                    <label class="btn btn-group-checkbox__label" for="elm_container_{$id}_show_on_{$device}">
                        {include_ext file="common/icon.tpl" class=$devices_icon}
                        {__("block_manager.availability.{$device}")}
                    </label>
                {/foreach}
            </div>
        </div>
    </div>

</fieldset>
</div>

<div class="buttons-container">
    {include file="buttons/save_cancel.tpl" but_name="dispatch[block_manager.update_location]" cancel_action="close" but_meta="cm-dialog-closer" save=$id}
</div>
</form>
<!--container_properties_{$id}--></div>

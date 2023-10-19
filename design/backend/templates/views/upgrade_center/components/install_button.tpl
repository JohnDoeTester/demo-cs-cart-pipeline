<div class="upgrade-center_install {if $show_package_contents}btn-group dropleft{/if}" id="install_button_{$id}">
    {if $show_pre_upgrade_notice}
        <input {if $form}form="{$form}"{/if} type="submit" class="btn btn-primary cm-dialog-opener cm-dialog-auto-size" value="{$caption}" data-ca-target-id="content_upgrade_center_wizard_{$id}">
    {else}
        <input {if $form}form="{$form}"{/if} type="submit" name="dispatch[upgrade_center.install]" class="btn btn-primary cm-ajax cm-comet cm-dialog-closer" value="{__("install")}">
    {/if}
    {if $show_package_contents}
        <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" tabindex="-1">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            <li>
                <a class="upgrade-center_pkg cm-dialog-opener cm-ajax"
                    href="{"upgrade_center.package_content?package_id=`$_id`"|fn_url}"
                    data-ca-target-id="package_content_{$id}"
                    data-ca-dialog-title="{$package_name|escape}"
                >{__("show_package_contents")}</a>
            </li>
        </ul>
    {/if}
<!--install_button_{$id}--></div>
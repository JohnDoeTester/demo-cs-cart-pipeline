{if !$addon.is_core_addon && $addon.identified}
    {$id = $id|default:"addons_contact_developer_sidebar"}

    <div>
        {capture name="contact_developer_form"}

            <form action="{""|fn_url}"
                method="post"
                enctype="multipart/form-data"
                name="addons_contact_developer_form_{$id}"
                class="form-horizontal form-edit"
            >
                <input type="hidden" name="redirect_url" value="{$config.current_url}" />
                <input type="hidden" name="marketplace_id" value="{$addon.marketplace_id}" />
                <input type="hidden" name="addon_name" value="{$addon.name}"/>
                <input type="hidden" name="addon_supplier" value="{$addon.supplier}">
                <input type="hidden" name="addon_id" value="{$addon.addon}"/>
                <input type="hidden" name="addon_version" value="{$addon_version}"/>
                <input type="hidden" name="language" value="{$smarty.const.CART_LANGUAGE}"/>
                <input type="hidden" name="country" value="{$settings.Checkout.default_country}"/>

                <fieldset>

                    <div class="control-group">
                        <label for="elm_addon_rating_name_{$id}" class="control-label">
                            {__("developer")}:
                        </label>
                        <div class="controls">
                            <p>
                                {$addon.supplier}
                            </p>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="elm_addon_rating_name_{$id}" class="control-label">
                            {__("addons.name")}:
                        </label>
                        <div class="controls">
                            <p>
                                {$addon.name}
                            </p>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="elm_addon_rating_name_{$id}" class="control-label">
                            {__("version")}:
                        </label>
                        <div class="controls">
                            <p>
                                {$addon_version}
                            </p>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="elm_addon_rating_message_{$id}" class="control-label cm-required">
                            {__("addons.your_message")}:
                        </label>
                        <div class="controls">
                            <textarea name="text" id="elm_addon_rating_text_{$id}" rows="7" class="input-large"></textarea>
                        </div>
                    </div>

                </fieldset>

                <div class="buttons-container">
                    <a class="cm-dialog-closer cm-cancel tool-link btn">{__("cancel")}</a>
                    {include file="buttons/button.tpl" but_role="submit" but_text=__("send") but_name="dispatch[addons.send_message]"}
                </div>

            </form>

        {/capture}

        {include file="common/popupbox.tpl"
            id=$id
            text=__("contact_the_developer", ["[developer]" => $addon.supplier])
            content=$smarty.capture.contact_developer_form
            link_text=__("contact_the_developer", ["[developer]" => $addon.supplier])
            link_class="btn-text"
            act="general"
            title=false
        }
    </div>
{/if}
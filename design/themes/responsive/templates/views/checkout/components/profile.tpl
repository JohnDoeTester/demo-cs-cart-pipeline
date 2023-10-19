<div class="ty-account ty-account--nopadding">
    <form method="POST" action="{""|fn_url}">
        {hook name="checkout:update_profile"}
            {if $profile_id}
                <input type="hidden" name="user_data[profile_id]" value="{$profile_id}" />
                {if $switch_after_update}
                    <input type="hidden" name="switch_after_update" value="1" />
                {/if}
            {else}
                <div class="ty-control-group ty-profile-field__item">
                    <label
                        for="elm_profile_name"
                        class="ty-control-group__title cm-profile-field cm-required cm-trim"
                    >{__("profile_name")}</label>
                    <input type="text" class="ty-input-text" name="user_data[profile_name]" id="elm_profile_name" />
                </div>
            {/if}

            {include
                file="views/profiles/components/profile_fields.tpl"
                section="ProfileFieldSections::SHIPPING_ADDRESS"|enum
                profile_fields=$profile_fields
                title=__("shipping_address")
                profile_data=$user_profile
                ship_to_another=$ship_to_another
            }

            {include
                file="views/profiles/components/profile_fields.tpl"
                section="ProfileFieldSections::BILLING_ADDRESS"|enum
                profile_fields=$profile_fields
                title=__("billing_address")
                profile_data=$user_profile
                grid_wrap="billing_address_fields"
                ship_to_another=$ship_to_another
                hide_fields=!$ship_to_another
            }
        {/hook}

        <div class="buttons-container">
            {if $profile_id}
                {include file="buttons/button.tpl" but_text=__("save") but_meta="ty-btn__secondary" but_role="submit" but_name="dispatch[checkout.update_profile]"}
            {else}
                {include file="buttons/button.tpl" but_text=__("create") but_meta="ty-btn__secondary" but_role="submit" but_name="dispatch[checkout.update_profile]"}
            {/if}
        </div>
    </form>
</div>

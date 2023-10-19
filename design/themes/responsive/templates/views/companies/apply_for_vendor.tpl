{hook name="vendors:apply_page"}
<div class="ty-company-fields">
    {include file="views/profiles/components/profiles_scripts.tpl"}

    <h1 class="ty-mainbox-title">{__("apply_for_vendor_account")}</h1>

    <div id="apply_for_vendor_account" >

        <form action="{"companies.apply_for_vendor"|fn_url}" method="post" name="apply_for_vendor_form" enctype="multipart/form-data">
            {if $invitation_key}
                <input type="hidden" name="company_data[invitation_key]" value="{$invitation_key}" />
            {/if}
            {hook name="vendors:apply_fields"}
                {include file="views/profiles/components/profile_fields.tpl" section="C" nothing_extra="Y" default_data_name="company_data" profile_data=$company_data}

                {hook name="vendors:apply_description"}
                {/hook}

                <input type="hidden" name="company_data[lang_code]" value="{$smarty.const.CART_LANGUAGE}" />
            {/hook}

            {include file="common/image_verification.tpl" option="apply_for_vendor_account" align="left"}

            <div class="buttons-container">
                {include file="buttons/button.tpl" but_text=__("submit") but_name="dispatch[companies.apply_for_vendor]" but_id="but_apply_for_vendor" but_meta="ty-btn__primary"}
            </div>
        </form>
    </div>
</div>
{/hook}
{if $profile_type == "ProfileTypes::CODE_SELLER"|enum}
    <option value="{$smarty.const.PROFILE_FIELD_TYPE_VENDOR_PLAN}" {if $field.field_type == $smarty.const.PROFILE_FIELD_TYPE_VENDOR_PLAN}selected="selected"{/if}>{__("vendor_plan")}</option>
{/if}

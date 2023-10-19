{if $page_type != $smarty.const.PAGE_TYPE_LINK}
    {if $addons.social_buttons.facebook_enable == "Y"}
        {include file="addons/social_buttons/common/facebook_types.tpl" object_type="page_data" object_data=$page_data}
    {/if}
{/if}

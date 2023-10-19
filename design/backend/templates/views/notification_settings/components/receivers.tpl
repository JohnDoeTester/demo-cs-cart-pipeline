<div class="notification-group__existing-receivers">
    {if $show_heading}
        <strong>{__("receivers")}:</strong>
    {/if}
    {foreach $receivers as $id => $condition}
        {capture name="receiver_value"}
            {hook name="notification_settings:receiver_value"}
                {include file="views/notification_settings/components/receivers/{$condition->getMethod()}.tpl"
                    value = $values.$id
                }
            {/hook}
        {/capture}
        <span class="notification-group-existing-receivers__item">{$smarty.capture.receiver_value|trim nofilter}</span>
    {/foreach}
</div>

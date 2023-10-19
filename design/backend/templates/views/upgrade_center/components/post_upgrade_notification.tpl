{$notifications = $notification_data.upgrade_notification_text}
{$emails = ", "|implode:$notification_data.email_recipients}
{$to_version = $notification_data.to_version}
{$changelog_url = $config.resources.changelog_url|default:"https://docs.cs-cart.com/latest/history/index.html"}
<div class="well">
    <h2>{__("upgrade_notification_welcome_title", ["[product]" => $smarty.const.PRODUCT_NAME, "[version]" => $to_version])}</h2>
    {__("upgrade_notification_welcome_text", ["[email]" => $emails]) nofilter}
    <p class="lead"><a href={$changelog_url} target="_blank" class="btn btn-primary">{__("view_changelog")}</a></p>
</div>
<h2>{__("upgrade_notification_what_check_first")}</h2>
{if !empty($notifications.required)}
    {foreach $notifications.required as $required_notification}
        <div class="alert alert-block">
            <h3><span class="label label-warning">{__("required")}</span> {$required_notification.title}</h3>
            {$required_notification.message nofilter}
        </div>
    {/foreach}
{/if}
{if !empty($notifications.important)}
    {foreach $notifications.important as $important_notification}
        <div class="alert alert-block alert-info">
            <h3>{$important_notification.title}</h3>
            {$important_notification.message nofilter}
        </div>
    {/foreach}
{/if}
{if !empty($notifications.common)}
    <h2>{__("upgrade_notification_what_else_to_check")}</h2>
    {foreach $notifications.common as $common_notification}
        <h3>{$common_notification.title}</h3>
        {$common_notification.message nofilter}
    {/foreach}
{/if}
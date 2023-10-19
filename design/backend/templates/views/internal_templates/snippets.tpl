{capture name="sidebar"}
    {include file="views/notification_settings/components/navigation_section.tpl" active_section=$active_section}
{/capture}

{$return_url = $config.current_url}
{$result_ids = $result_ids|default:"content_snippets"}
{$type = $type|default:"mail"}
{$addon = $addon|default:""}

{capture name="mainbox"}
<div id="content_snippets">
    {include file="views/snippets/components/list.tpl"
        snippets=$snippets
        type="mail"
        addon=""
        result_ids="content_snippets"
        return_url=$return_url
    }
<!--content_snippets--></div>
{/capture}

{capture name="buttons"}
    {include file="views/snippets/components/tools_list.tpl" icon=$icon}
{/capture}
{capture name="adv_buttons"}
    {include file="views/snippets/components/adv_buttons.tpl" result_ids=$result_ids return_url=$return_url type=$type addon=$addon text=$text}
{/capture}

{include file="common/mainbox.tpl"
    title=__("snippets")
    content=$smarty.capture.mainbox
    adv_buttons=$smarty.capture.adv_buttons
    buttons=$smarty.capture.buttons
    sidebar=$smarty.capture.sidebar
}
{capture name="mainbox"}
    {$c_url=""|fn_url}
    {__("unsupported_browser_notice", ["[url]" => $c_url]) nofilter}
{/capture}
{include file="common/mainbox.tpl" title=__("browser_upgrade_notice_title") content=$smarty.capture.mainbox}
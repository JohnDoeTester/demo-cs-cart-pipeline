{$relative_dropdown = $relative_dropdown|default:true}

{capture name="ajax_select_content"}

<a {if $span_wrapping == false}id="sw_{$id}_wrap_"{/if} class="{if $type != "list"}btn-text{/if} dropdown-toggle" data-toggle="dropdown">
    {if $span_wrapping}
        <span id="sw_{$id}_wrap_">{$text|truncate:40:"...":true}</span>
        {if $dropdown_icon}
            {include_ext file="common/icon.tpl"
                class="`$dropdown_icon` dropdown-menu__icon"
            }
        {/if}
        <b class="caret"></b>
    {else}
        {$text|truncate:40:"...":true}
        {if $dropdown_icon}
            {include_ext file="common/icon.tpl"
                class="`$dropdown_icon` dropdown-menu__icon"
            }
        {/if}
        <b class="caret"></b>
    {/if}
</a>

{if $label}<label>{$label}</label>{/if}

{if $js_action}
<script>
(function(_, $) {
    $.ceEvent('on', 'ce.picker_js_action_{$id}', function(elm) {
        {$js_action nofilter}
    });
}(Tygh, Tygh.$));
</script>
{/if}

<ul
    class="dropdown-menu {if $type == "opened"}dropdown-opened{/if}"
    id="{$id}_ajax_select_object"
    {if $extra_data_old_id}data-ca-target-old-id="{$extra_data_old_id}"{/if}
    {if $extra_data_new_id}data-ca-target-new-id="{$extra_data_new_id}"{/if}
>
    <li>
        <div id="{$id}_wrap_" class="search-shop cm-smart-position">
            <input type="text" placeholder="{__("admin_search_field")}..." class="span3 input-text cm-ajax-content-input" data-ca-target-id="content_loader_{$id}" size="16">
        </div>
    </li>
    <li>
        <div class="ajax-popup-tools" id="scroller_{$id}">
            <ul class="cm-select-list" id="{$id}">
            {foreach from=$objects key="object_id" item="item"}
                {if $runtime.customization_mode.live_editor}
                    {assign var="name" value=$item.name}
                {else}
                    {assign var="name" value=$item.name|truncate:40:"...":true}
                {/if}
                <li>
                    <a data-ca-action="{$item.value}" title="{$item.name}">
                        {$name}
                        {if $object_type === "companies" && $item.storefront_status === "StorefrontStatuses::CLOSED"|enum}
                            {include_ext file="common/icon.tpl"
                                class="icon-lock dropdown-menu__item-icon"
                            }
                        {/if}
                    </a>
                </li>
            {/foreach}
            <!--{$id}--></ul>
            <ul>
                <li id="content_loader_{$id}" class="cm-ajax-content-more ajax-content-more" data-ca-target-url="{$data_url|fn_url}" data-ca-target-id="{$id}" data-ca-result-id="{$result_elm}"><span>{__("loading")}</span></li>
            </ul>
        </div>
    </li>
    {$extra_content nofilter}
</ul>
{/capture}

{if $type == 'list'}
    <li class="{if $relative_dropdown}dropdown{/if} vendor-submenu">{$smarty.capture.ajax_select_content nofilter}</li>
{else}
    <div class="{if $relative_dropdown}btn-group{/if}">{$smarty.capture.ajax_select_content nofilter}</div>
{/if}

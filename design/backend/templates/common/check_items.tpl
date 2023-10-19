{$check_data = ""}
{$container = ($elms_container) ? "data-ca-container={$elms_container}" : ""}

{if $check_target}
    {$check_data = "data-ca-target=\"`$check_target`\""}
{/if}

{capture name="check_items_checkbox"}
    {if $style == "links"}
        <a 
            {if $check_link} href="{$check_link}" {/if} 
            class="cm-check-items cm-on underlined" 
            {$check_data nofilter}
        >
            {__("select_all")}
        </a> | <a 
            {if $check_link} href="{$check_link}" {/if} 
            class="cm-check-items cm-off underlined" 
            {$check_data nofilter}
        >
            {__("unselect_all")}
        </a>
    {else}
        <input 
            type="checkbox" 
            name="check_all" 
            value="Y" 
            title="{__("check_uncheck_all")}" 
            class="{if $check_statuses}pull-left{/if} cm-check-items {$class}" 
            {if $check_onclick}onclick="{$check_onclick}"{/if} 
            {$check_data nofilter}
            {if $checked}checked="checked"{/if}
            {if $is_check_disabled}disabled="disabled"{/if} 
        />
    {/if}
{/capture}

{if ($check_statuses || $is_check_all_shown) && !$is_check_disabled} 
    {if !$wrap_select_actions_into_dropdown}
    <div class="btn-group btn-checkbox cm-check-items {$meta}">
        <a href="" data-toggle="dropdown" class="btn dropdown-toggle">
            <span class="caret"></span>
        </a>
        {$smarty.capture.check_items_checkbox nofilter}
    {/if}
        <ul class="dropdown-menu {$dropdown_menu_class}">
            <li><a class="cm-on" {$check_data nofilter} {$container} >{__("check_all")}</a></li>
            <li><a class="cm-off" {$check_data nofilter} {$container} >{__("check_none")}</a></li>
            {foreach $check_statuses as $status => $title}
            <li><a {$check_data nofilter} data-ca-status="{$status|lower}" {$container} >{$title}</a></li>
            {/foreach}
        </ul>
    {if !$wrap_select_actions_into_dropdown}
    </div>
    {/if}
{else}
    <div class="{$meta}">
        {$smarty.capture.check_items_checkbox nofilter}
    </div>
{/if}

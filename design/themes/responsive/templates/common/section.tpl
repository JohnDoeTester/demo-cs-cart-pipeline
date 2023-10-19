{$id = $section_title|md5|string_format:"s_%s"}
{$rnd = rand()}
{if $smarty.cookies.$id || $collapse}
    {$collapse = true}
{else}
    {$collapse = false}
{/if}

<div class="ty-section{if $class} {$class}{/if}" id="ds_{$rnd}">
    <div  class="ty-section__title {if !$collapse}open{/if} cm-combination cm-save-state cm-ss-reverse" id="sw_{$id}">
        <span>{$section_title nofilter}</span>
        <span class="ty-section__switch ty-section_switch_on">{__("open_action")}{include_ext file="common/icon.tpl" class="ty-icon-down-open ty-section__arrow" id=""}</span>
        <span class="ty-section__switch ty-section_switch_off">{__("hide")}{include_ext file="common/icon.tpl" class="ty-icon-up-open ty-section__arrow" id=""}</span>
    </div>
    <div id="{$id}" class="{$section_body_class|default:"ty-section__body"} {if $collapse}hidden{/if}">{$section_content nofilter}</div>
</div>

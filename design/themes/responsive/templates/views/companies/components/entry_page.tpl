<ul class="ty-entry-page__countries">
    {foreach name="countries" from=$countries key="code" item="url"}
        <li class="ty-entry-page__item"><a class="ty-entry-page__a" href="{$url}">{include_ext file="common/icon.tpl" class="ty-flag ty-flag-`$code|lower`"}{$country_descriptions.$code.country}</a></li>
    {/foreach}
</ul>

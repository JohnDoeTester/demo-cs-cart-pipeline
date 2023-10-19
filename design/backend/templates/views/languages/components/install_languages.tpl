<div class="hidden" id="content_available_languages">
    {if $langs_meta|count > 0}
        {* FIXME: HARDCODE checking permissions. We need to divide these two forms by different modes *}
        <form action="{""|fn_url}" method="post" name="languages_install_form" class="{if $runtime.company_id}cm-hide-inputs{/if}">
            <input type="hidden" name="page" value="{$smarty.request.page}" />
            <input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />

            <div class="table-responsive-wrapper">
                <table class="table table-middle table--relative table-responsive">
                <thead>
                    <tr class="cm-first-sibling">
                        <th>{__("language_code")}</th>
                        <th>{__("name")}</th>
                        <th>{__("country")}</th>
                        <th class="right">&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                {foreach from=$langs_meta item="language"}
                    <tr>
                        <td data-th="{__("language_code")}">
                            {$language.lang_code}
                        </td>
                        <td data-th="{__("name")}">
                            {$language.name}
                        </td>
                        <td data-th="{__("country")}">
                            {include_ext file="common/icon.tpl"
                                class="flag flag-`$language.country_code|strtolower`"
                            }{$countries[$language.country_code]}
                        </td>
                        <td class="right" data-th="">
                            {include
                                file="buttons/button.tpl"
                                but_href="{"languages.install?pack=`$language.lang_code`"|fn_url}"
                                but_text=__("install")
                                but_role="action"
                                but_meta="lowercase cm-post"
                            }
                        </td>

                    </tr>
                {/foreach}
                </tbody>
                </table>
            </div>

        </form>
    {else}
        <p class="no-items">{__("no_items")}</p>
    {/if}
<!--content_available_languages--></div>

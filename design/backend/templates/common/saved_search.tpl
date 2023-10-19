{$new_search = $allow_new_search|default: true}
{$views = $view_type|fn_get_views}
{$max_items = 4}
{$return_current_url = $config.current_url|fn_query_remove:"view_id":"new_view"}
{$redirect_current_url = $config.current_url|escape:url}

{hook name="advanced_search:views"}
    {if $views}
        <div class="sidebar-row" id="views">
            <h6>{__("saved_search")}</h6>
            <ul class="nav nav-list saved-search">
                {if $views}
                    <li {if !$search.view_id && !$search.temp_view}class="active"{/if}>
                        <a href="{"`$dispatch`.reset_view?`$view_suffix`"|fn_url}">{__("all")}</a>
                    </li>
                    {foreach $views as $view name=views}
                        {if $smarty.foreach.views.index == $max_items}
                            {$s_id = $dispatch|fn_crc32|string_format:"saved_searches_%s"}
                            <li>
                                <span class="more hand">
                                    <a id="on_{$s_id}" class="collapsed cm-combination cm-save-state {if $smarty.cookies.$s_id}hidden{/if}">
                                        {__("more")}{include_ext file="common/icon.tpl" class="icon-caret-down"}
                                    </a>
                                    <a id="off_{$s_id}" class="cm-combination cm-save-state {if !$smarty.cookies.$s_id}hidden{/if}">
                                        {__("more")}{include_ext file="common/icon.tpl" class="icon-caret-down"}
                                    </a>
                                </span>
                            </li>
                            <li id="{$s_id}" class="{if !$smarty.cookies.$s_id}hidden{/if}">
                                <ul class="nav nav-list">
                        {/if}
                        <li class="{if $view.view_id == $search.view_id}active{/if} saved-search__item">
                            <a class="cm-view-name saved-search__item-name
                            {if $last_view_current_object_schema.allow_default_view}
                                saved-search__item-name--default-view
                            {/if}
                            "
                                data-ca-view-id="{$view.view_id}"
                                href="{"`$dispatch`?view_id=`$view.view_id``$view_additional_parameters`&`$view_suffix`"|fn_url}"
                            >
                                {$view.name}
                            </a>

                            {if $last_view_current_object_schema.allow_default_view}
                                {if $view.is_default === "YesNo::YES"|enum}
                                    <a href="{"`$dispatch`.unset_default_view?view_id=`$view.view_id`&redirect_url=`$redirect_current_url`"|fn_url}"
                                        class="cm-confirm cm-tooltip icon-pushpin nav-list__btn saved-search__pin saved-search__pin--pinned"
                                        {([
                                            "data-ca-confirm-text" => __("saved_search.set_as_non_default_confirm", [
                                                "[name]" => $view.name
                                            ]),
                                            "title" => __("saved_search.set_as_non_default")
                                        ])|render_tag_attrs nofilter}
                                    >
                                    </a>
                                {else}
                                    <a href="{"`$dispatch`.set_default_view?view_id=`$view.view_id`&redirect_url=`$redirect_current_url`"|fn_url}"
                                        class="cm-confirm cm-tooltip icon-pushpin nav-list__btn saved-search__pin saved-search__pin saved-search__pin--unpinned"
                                        {([
                                            "data-ca-confirm-text" => __("saved_search.set_as_default_confirm", [
                                                "[name]" => $view.name
                                            ]),
                                            "title" => __("saved_search.set_as_default")
                                        ])|render_tag_attrs nofilter}
                                    >
                                    </a>
                                {/if}
                            {/if}
                            {if $new_search}
                                <a href="{"`$dispatch`.delete_view?view_id=`$view.view_id`&redirect_url=`$redirect_current_url`"|fn_url}"
                                    class="cm-confirm cm-tooltip icon-trash nav-list__btn saved-search__delete"
                                    title="{__("delete")}"
                                >
                                </a>
                            {/if}
                        </li>
                    {/foreach}

                    {if $search.temp_view}
                         <li class="active">
                             <a href="#">{__("custom_search")}</a>
                         </li>
                    {/if}

                    {if $smarty.foreach.views.total > $max_items}
                            </ul>
                        </li>
                    {/if}
                {/if}
                {if $new_search}
                    <li class="last">
                        {include file="buttons/button.tpl" but_text=__("new_saved_search") but_role="text" but_meta="text-button cm-dialog-opener" but_target_id="adv_search"}
                    </li>
                {/if}
            </ul>
        </div>
        <hr>
    {/if}
{/hook}

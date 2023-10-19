<div class="sidebar-row">
    <form action="{""|fn_url}" name="addons_filters" method="get" class="{$form_meta}" id="addon_filters">
        <div class="sidebar-field">
            <strong>{__("developers")}</strong>
            {foreach $developers as $developer_key => $developer}
                <label class="control-label checkbox" for="supplier_{$developer_key}">
                    <input type="checkbox" id="supplier_{$developer_key}" name="supplier[]" value="{$developer.title}" {if $developer.title|in_array:$search.supplier} checked="checked"{/if}>
                    <span>
                        {$developer.title} ({$developer.position})
                    </span>
                </label>
            {/foreach}
        </div>
        <div class="sidebar-field">
            <strong>{__("addon_type")}</strong>
            <label class="control-label checkbox" for="third_party_addons">
                <input type="checkbox" id="third_party_addons" name="source" value="third" {if $search.source} checked="checked"{/if}>
                <span>
                    {__("third_party_addons")}
                </span>
            </label>
            <label class="control-label checkbox" for="without_rating">
                <input type="checkbox" id="without_rating" name="without_rating" value="{"YesNo::YES"|enum}" {if $search.without_rating} checked="checked"{/if}>
                {__("without_rating")}
            </label>
            <label class="control-label checkbox" for="additional_pages">
                <input type="checkbox" id="additional_pages" name="add_pages" value="{"YesNo::YES"|enum}" {if $search.add_pages} checked="checked"{/if}>
                <span>
                    {__("has_additional_pages")}
                </span>
            </label>
            <label class="control-label checkbox" for="favorites">
                <input type="checkbox" id="favorites" name="favorites" value="{"YesNo::YES"|enum}" {if $search.favorites} checked="checked"{/if}>
                <span>
                {__("favorites")}
                </span>
            </label>
        </div>
        <div class="sidebar-field">
            <label for="addon_status"><strong>{__("status")}</strong></label>
            <select id="addon_status" name="type">
                <option value="any" {if empty($search.type) || $search.type == "any"} selected="selected"{/if}>{__("any")}</option>
                <option value="not_installed" {if $search.type == "not_installed"} selected="selected"{/if}>{__("not_installed")}</option>
                <option value="installed" {if $search.type == "installed"} selected="selected"{/if}>{__("installed")}</option>
                <option value="active" {if $search.type == "active"} selected="selected"{/if}>{__("active")}</option>
                <option value="disabled" {if $search.type == "disabled"} selected="selected"{/if}>{__("disabled")}</option>
            </select>
        </div>
        <div class="sidebar-field">
            <strong>{__("install_date")}</strong>

            <select name="{$prefix}period" id="{$id_prefix}period_selects">
                <option value="A" {if $search.period === "A" || !$period}selected="selected"{/if}>{__("all")}</option>
                <optgroup label="=============">
                    <option value="D" {if $search.period === "D"}selected="selected"{/if}>{__("this_day")}</option>
                    <option value="W" {if $search.period === "W"}selected="selected"{/if}>{__("this_week")}</option>
                    <option value="M" {if $search.period === "M"}selected="selected"{/if}>{__("this_month")}</option>
                    <option value="Y" {if $search.period === "Y"}selected="selected"{/if}>{__("this_year")}</option>
                </optgroup>
                <optgroup label="=============">
                    <option value="LD" {if $search.period === "LD"}selected="selected"{/if}>{__("yesterday")}</option>
                    <option value="LW" {if $search.period === "LW"}selected="selected"{/if}>{__("previous_week")}</option>
                    <option value="LM" {if $search.period === "LM"}selected="selected"{/if}>{__("previous_month")}</option>
                    <option value="LY" {if $search.period === "LY"}selected="selected"{/if}>{__("previous_year")}</option>
                </optgroup>
                <optgroup label="=============">
                    <option value="HH" {if $search.period === "HH"}selected="selected"{/if}>{__("last_24hours")}</option>
                    <option value="HW" {if $search.period === "HW"}selected="selected"{/if}>{__("last_n_days", ["[N]" => 7])}</option>
                    <option value="HM" {if $search.period === "HM"}selected="selected"{/if}>{__("last_n_days", ["[N]" => 30])}</option>
                </optgroup>
            </select>
        </div>
        <div class="sidebar-field addon-compatibility">
            <strong>{__("compatibility")}</strong>
            <select name="store_version" id="version_options">
                <option value="" selected="selected">{__("any")}</option>
                {foreach $versions as $version}
                    <option value="{$version}" {if $search.store_version === $version}selected="selected"{/if}>{$version}</option>
                {/foreach}
            </select>
            <div class="muted addon-compatibility__version">
                {include file="common/product_release_info.tpl" is_time_shown=false assign="release_info"}
                {__("product_env.now_running", ["[release_info]" => $release_info])}
            </div>
        </div>

        <div class="sidebar-field advanced-search-field">
            <input class="btn" type="submit" name="dispatch[{$dispatch}]" value="{__("admin_search_button")}">
            <a class="btn btn-text" href="{"addons.manage.reset_view"|fn_url}">{__("reset")}</a>
        </div>
    </form>
</div>

{if !$addon.is_core_addon && $addon.identified && !$personal_review && !$addon.hide_post_review}
    {$title = ($title_full)
        ? __("addons.is_enjoying_addon_name", ["[addon]" => $addon.name])
        : __("addons.is_enjoying_addon")
    }
    {$id = $id|default:"addons_write_review"}
    {$ratings = ["1", "2", "3", "4", "5"]}
    {$is_big_heading = $is_big_heading|default:true}

    {capture name="addons_write_review_title"}
        {$title nofilter}
    {/capture}

    {if $is_big_heading}
        <h4>
            {$smarty.capture.addons_write_review_title nofilter}
        </h4>
    {else}
        <div>
            <strong>
                {$smarty.capture.addons_write_review_title nofilter}
            </strong>
        </div>
    {/if}

    <div>
        {__("addons.tap_star_to_rate_on_marketplace")}
    </div>

    <div>
        {capture name="addons_write_review"}

            <div id="addons_review_form_wrapper_{$id}">

                <form action="{""|fn_url}"
                    method="post"
                    enctype="multipart/form-data"
                    name="addons_review_form_{$id}"
                    class="form-horizontal form-edit cm-processed-form cm-check-changes"
                >
                    <input type="hidden" name="redirect_url" value="{$config.current_url}" />
                    <input type="hidden" name="marketplace_id" value="{$addon.marketplace_id}" />

                    <fieldset>

                        <div class="control-group">
                            <label for="elm_addon_rating_name_{$id}" class="control-label">
                                {__("addons.name")}
                            </label>
                            <div class="controls">
                                <p>
                                    {$addon.name}
                                </p>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label cm-required cm-multiple-radios" for="elm_addon_rating_value_rating_{$id}">
                                {__("rating")}
                            </label>
                            <div class="controls">

                                {foreach $ratings as $rating}

                                    <label for="elm_addon_rating_value_{$rating}_{$id}" class="radio inline">
                                        {__("n_stars", [$rating])}
                                        <input type="radio"
                                            name="value"
                                            value="{$rating}"
                                            id="elm_addon_rating_value_{$rating}_{$id}"
                                            {if $rating === "5"}
                                                checked="checked"
                                            {/if}
                                        />
                                    </label>

                                {/foreach}

                            </div>
                        </div>

                        <div class="control-group">
                            <label for="elm_addon_rating_message_{$id}" class="control-label cm-required">
                                {__("addons.message")}
                            </label>
                            <div class="controls">
                                <textarea name="message" id="elm_addon_rating_message_{$id}" rows="7" class="input-large"></textarea>
                            </div>
                        </div>

                    </fieldset>

                    <div class="buttons-container">
                        <a class="cm-dialog-closer cm-cancel tool-link btn">{__("cancel")}</a>
                        {include file="buttons/button.tpl" but_role="submit" but_text=__("submit") but_name="dispatch[addons.set_rating]"}
                    </div>

                </form>
            </div>

        {/capture}

        {include file="common/popupbox.tpl"
            id=$id
            text=__("addons.write_review")
            content=$smarty.capture.addons_write_review
            link_text="☆☆☆☆☆"
            link_class="btn-large btn-text"
            act="general"
            title=false
        }
    </div>
{/if}
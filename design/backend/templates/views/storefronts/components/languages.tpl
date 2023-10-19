{*
array   $id                              Storefront ID
array   $all_language_ids                All languages ids
array   $all_languages                   All languages
boolean $$is_localization_picker_allowed Is localization picker allowed
*}

<div class="control-group">
    <label for="languages_{$id}"
           class="control-label"
    >
        {__("languages")}
    </label>
    <div class="controls" id="languages_{$id}">
        {if $is_localization_picker_allowed}
            {include file="common/adaptive_object_selection.tpl"
                input_name="storefront_data[languages]"
                input_id="storefront_language"
                item_ids=$all_language_ids
                items=$all_languages
                id_field="lang_id"
                name_field="name"
                storefront_id=$id
                type="languages"
                load_items_url="languages.selector?storefront_id=`$id`"
                class_prefix="localization"
                close_on_select="false"
            }
        {else}
            {foreach $all_languages as $language}
                {if $language.lang_id|in_array:$all_language_ids}
                    <p>{$language.name}</p>
                {/if}
            {/foreach}
        {/if}
    </div>
</div>

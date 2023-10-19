<div class="object-picker__categories-main">
    {if $type === "result" || $type === "selection" || $type === "selection_external"}
        <div class="object-picker__categories-main-content">
            {$title_pre}
                {if $has_selection_controls}
                    <input class="select2__category-status-checkbox cm-tristate tristate-checkbox-cursor {$categories_picker_item_class}"
                        type="checkbox"
                        data-ca-category-id="{literal}${data.id}{/literal}"
                        data-ca-tristate-process="false"
                        data-ca-tristate-just-click=""
                        data-checked=""
                    />
                {/if}
                <span class="select2-selection__choice__handler"></span>
                <div class="select2__category-name">
                    {if $type === "selection_external"}
                        <a href="{literal}${data.url}{/literal}">{literal}${data.name}{/literal}</a>
                    {else}
                        {literal}${data.name}{/literal}
                    {/if}
                </div>
                <div class="select2__category-parents">
                    {literal}${data.parents_path ? data.parents_path : ``}{/literal}
                </div>

                {if !$runtime.simple_ultimate}
                    {literal}
                        ${data.company
                        ? `<div class="select2__category-company">${data.company}</div>`
                            : ``
                        }
                    {/literal}
                {/if}
            {$title_post}
        </div>
    {elseif $type === "load"}
        ...
    {elseif $type === "new_item"}
        <div class="object-picker__results-label object-picker__results-label--categories">
            {if $icon|default:true}
                <div class="object-picker__results-label-icon-wrapper object-picker__results-label-icon-wrapper--categories">
                    {include_ext file="common/icon.tpl"
                        class="`$icon|default:'icon-plus-sign'` object-picker__results-label-icon object-picker__results-label-icon--categories"
                    }
                </div>
            {/if}
            {if $title_pre}
                <div class="object-picker__results-label-prefix object-picker__results-label-prefix--categories">
                    {$title_pre}
                </div>
            {/if}
            <div class="object-picker__results-label-body object-picker__results-label-body--categories">
                <span class="select2-selection__choice__handler"></span>
                {literal}${data.text}{/literal}
            </div>
        </div>

        {if $help}
            <div class="object-picker__results-help object-picker__results-help--categories">
                {__("enter_category_name_and_path")}
            </div>
        {/if}
    {/if}
</div>

{*
    $id      string Dropdown menu ID
    $data    array  Data from context_menu schema
    $content string Dropdown menu content
*}

<li {$data.menu_item_attributes|render_tag_attrs nofilter}
    {if !$data.menu_item_attributes.class}
        class="btn bulk-edit__btn bulk-edit__btn--{$id} dropleft-mod {$data.menu_item_class}"
    {/if}
>
    <span class="bulk-edit__btn-content bulk-edit-toggle bulk-edit__btn-content--{$id}"
          data-toggle=".bulk-edit__content--{$id}"
    >
        {__($data.name.template, $data.name.params)}
        <span class="caret mobile-hide"></span>
    </span>

    <div class="bulk-edit--reset-dropdown-menu  bulk-edit__content bulk-edit__content--{$id}">
        <div class="bulk-edit-inner bulk-edit-inner--{$id}">
            {$content nofilter}
        </div>
    </div>

    <div class="bulk-edit--overlay"></div>
</li>

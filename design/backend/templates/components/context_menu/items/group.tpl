{*
    $item_id string                            Item identifier
    $item    \Tygh\ContextMenu\Items\GroupItem Group item
    $data    array                             Data from context_menu schema
    $params  array                             Сontext menu component parameters
*}

<li {$data.menu_item_attributes|render_tag_attrs nofilter}
    {if !$data.menu_item_attributes.class}
        class="btn bulk-edit__btn bulk-edit__btn--{$item_id} dropleft-mod {$data.menu_item_class}"
    {/if}
>
    <span class="bulk-edit__btn-content dropdown-toggle" data-toggle="dropdown">{__($data.name.template, $data.name.params)} <span class="caret mobile-hide"></span></span>

    <ul class="dropdown-menu">
        {foreach $item->getItems() as $item_id => $subitem}
            {include file=$subitem->getTemplate()
                item_id=$item_id
                item=$subitem
                data=$subitem->getData()
            }
        {/foreach}
    </ul>
</li>

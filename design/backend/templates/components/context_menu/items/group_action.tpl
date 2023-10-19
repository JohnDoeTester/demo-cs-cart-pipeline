{*
    $item_id string                                  Item identifier
    $item    \Tygh\ContextMenu\Items\GroupActionItem Group action item
    $data    array                                   Data from context_menu schema
    $params  array                                   Сontext menu component parameters
*}

<li {$data.menu_item_attributes|render_tag_attrs nofilter}
    {if !$data.menu_item_attributes.class}
        class="{$data.menu_item_class}"
    {/if}
>
    <a {$data.action_attributes|render_tag_attrs nofilter}
            {if !$data.action_attributes.class}
                class="cm-process-items cm-submit {$data.action_class}"
            {/if}
            {if !$data.action_attributes["data-ca-target-form"]}
                data-ca-target-form="{$params.form}"
            {/if}
            {if !$data.action_attributes["data-ca-dispatch"]}
                data-ca-dispatch="dispatch[{$data.dispatch}]"
            {/if}
    >
        {__($data.name.template, $data.name.params)}
    </a>
</li>

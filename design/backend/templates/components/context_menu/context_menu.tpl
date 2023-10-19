{*
    $status_selector    \Tygh\StatusSelector Status selector object
    $context_menu_items array                An array of context menu items data
    $params             array                Сontext menu component parameters
*}

<div class="bulk-edit clearfix hidden {$params.class}"
     {if $context_menu_items}
         data-ca-bulkedit-expanded-object="true"
         data-ca-bulkedit-component="expandedObject"
     {else}
         data-ca-bulkedit-disabled="true"
     {/if}
>

    <ul class="btn-group bulk-edit__wrapper">
        {include file=$status_selector->getTemplate()
            statuses=$status_selector->getStatuses()
            elms_container=$context_menu_id
        }

        {foreach $context_menu_items as $item_id => $item}
            {include file=$item->getTemplate()
                item_id=$item_id
                item=$item
                data=$item->getData()
            }
        {/foreach}
    </ul>

</div>

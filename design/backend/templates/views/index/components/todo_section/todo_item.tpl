{if $todo_item}
    {if $todo_item.action_text && $todo_item.action_url}
        <div class="todo-item todo-item--{$todo_item.type|lower}" id="{$todo_item.id}">
            <div class="todo-item__inner todo-item__inner--{$todo_item.type|lower}">
                <div class="todo-item__content">{$todo_item.text nofilter}</div>
                <div class="todo-item__actions">
                    <a href="{$todo_item.action_url|fn_url}" class="btn todo-item__button {if $todo_item.is_ajax_action}cm-ajax{/if}">
                        {$todo_item.action_text nofilter}
                    </a>
                </div>
            </div>
        <!--{$todo_item.id}--></div>
    {elseif $todo_item.action_url}
        <a href="{$todo_item.action_url|fn_url}" class="todo-item todo-item--{$todo_item.type|lower} todo-item--link" id="{$todo_item.id}">
            <div class="todo-item__inner todo-item__inner--{$todo_item.type|lower} todo-item__inner--no-responsive">
                <div class="todo-item__content">{$todo_item.text nofilter}</div>
                <div class="todo-item__actions">
                    {include_ext file="common/icon.tpl" class="icon-chevron-right todo-item__button-icon"}
                </div>
            </div>
        <!--{$todo_item.id}--></a>
    {else}
        <div class="todo-item todo-item--{$todo_item.type|lower}" id="{$todo_item.id}">
            <div class="todo-item__inner todo-item__inner--{$todo_item.type|lower}">
                <div class="todo-item__content">{$todo_item.text nofilter}</div>
            </div>
        <!--{$todo_item.id}--></div>
    {/if}
{/if}

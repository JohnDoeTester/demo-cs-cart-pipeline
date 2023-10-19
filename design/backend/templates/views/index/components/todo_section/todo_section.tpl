{strip}
{*
    $show_todo_section
    $dashboard_alert
    $auth
    $is_activated_free
    $settings
*}

{$show_todo_section = $show_todo_section|default:true}

{if $show_todo_section && $todo_data}
    <section class="todo-section">
        <div class="todo-section__content">
            {foreach $todo_data as $todo_section}
                <div class="todo-section__column todo-section__column--{$todo_section.section|lower}" id="{$todo_section.id}">
                    {foreach $todo_section.content as $todo_item}
                        {include file="views/index/components/todo_section/todo_item.tpl"
                            todo_item=$todo_item
                        }
                    {/foreach}
                <!--{$todo_section.id}--></div>
            {/foreach}
        </div>
    </section>
{/if}
{/strip}
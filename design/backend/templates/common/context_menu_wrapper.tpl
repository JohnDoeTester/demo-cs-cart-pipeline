{*
  $object             string        Context menu object
  $form               string        Name of the form the context menu associated with
  $items              string        Items that are managed by the context menu
  $id                 stirng        Context menu wrapper ID
  $class              string        Context menu wrapper class
  $attributes         array<string> Any additional attributes to render
  $hook               string|null   Hook name to wrap comntext menu into
  $is_check_all_shown bool          Whether to display "Select all" in status selector
  $has_permission     bool          Show context menu based on user rights
  $context_menu_class string        Context menu class
*}
{$id = $id|default:"{uniqid()}"}
{$class = $class|default:""}
{$attributes = $attributes|default:[]}
{$attributes["data-ca-longtap"] = true}
{$hook = $hook|default:"`$object`:context_menu"}
{$has_permission = $has_permission|default:true}
{$context_menu_class = $context_menu_class|default:""}

<div class="{$class}" id="{$id}" {$attributes|render_tag_attrs}>
    {if $has_permission}
        {hook name = $hook}
            {component
                name = "context_menu.context_menu"
                object = $object
                form = $form
                class = $context_menu_class
                context_menu_id = "#{$id}"
                is_check_all_shown = $is_check_all_shown
            }{/component}
        {/hook}
    {/if}

    {$items nofilter}
</div>

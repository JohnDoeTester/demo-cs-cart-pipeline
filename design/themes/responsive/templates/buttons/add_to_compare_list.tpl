{if !$config.tweaks.disable_dhtml}
    {$ajax_class = "cm-ajax cm-ajax-full-render"}
{/if}

{if !$hide_compare_list_button}
    {$c_url               = $redirect_url|default:         $config.current_url|escape:url}
    {$compare_button_type = $compare_button_type|default:  "icon"}
    {$but_title           = $compare_but_title|default:    __("add_to_comparison_list")}
    {$but_href            = $compare_but_href|default:     "product_features.add_product?product_id=$product_id&redirect_url=$c_url"}
    {$but_role            = $compare_but_role|default:     "text"}
    {$but_target_id       = $compare_but_target_id|default:"comparison_list,account_info*"}
    {$but_rel             = $compare_but_rel|default:      "nofollow"}

    {if $compare_button_type == "icon"}
        {$but_icon        = $compare_but_icon|default:     "ty-icon-chart-bar"}
        {$but_text        = $compare_but_text|default:     false}
        {$but_meta        = $compare_but_meta|default:     "ty-btn__tertiary ty-btn-icon ty-add-to-compare $ajax_class"}
    {else}
        {$but_icon        = ($compare_but_icon === true) ? "ty-icon-chart-bar" : $compare_but_icon}
        {$but_text        = $compare_but_text|default:     __("add_to_comparison_list")}
        {$but_meta        = $compare_but_meta|default:     "ty-btn__text ty-add-to-compare $ajax_class"}
    {/if}

    {include file="buttons/button.tpl"
        but_text=$but_text
        but_title=$but_title
        but_href=$but_href
        but_role=$but_role
        but_target_id=$but_target_id
        but_meta=$but_meta
        but_rel=$but_rel
        but_icon=$but_icon
    }
{/if}

{$add_navbar = $add_navbar|default:true}
{$add_bottom_tabs = $add_bottom_tabs|default:true}
{$pickers_available = $pickers_available|default:true}
{$is_button = $is_button|default:false}
{$navbar_title = $navbar_title|default:"CS-Cart"}
{$add_add_button = $add_add_button|default:false}
{$background_color_class=$background_color_class|default:"screenBackgroundColor__background"}
{$status_bar_base_style=$status_bar_base_style|default:false}

<div class="span16 mockup__mockups-container">
    <div class="span4 mockup">
        <div class="mockup__decor"></div>

        <div class="mockup__container">
            <div class="mockup__status-bar {if $status_bar_base_style}mockup__status-bar--base-style{else}navBarBackgroundColor__background statusBarIconColor{/if}">
                <span>5:05</span>
                <div class="mockup__status-bar--icons">
                    <span>...</span>
                    <i class="fa fa-wifi"></i>
                    <i class="fa fa-battery-half"></i>
                </div>
            </div>

            {if $add_navbar}
                {include 
                    file="addons/mobile_app/components/atoms/navbar.tpl" 
                    title=$navbar_title 
                    is_button=$is_button
                    back_icon=$back_icon
                    uppercase_title=$uppercase_title
                }
            {/if}

            <div class="mockup__body body {$background_color_class} {$meta}" style="min-height: calc(100% - 65px); max-height: calc(100% - 65px);">
                {$content nofilter}
            </div>

            {if $add_bottom_tabs}
                {include file="addons/mobile_app/components/atoms/bottom_tabs.tpl"}
            {/if}

            {if $add_add_button}
            <div class="mockup__product-add-to-cart">
                <button class="mockup__product-add-to-cart--action buttonWithBackgroundTextColor buttonBackgroundColor__background" disabled>Add to cart</button>
            </div>
            {/if}
        </div>

        <div class="mockup__decor-line"></div>
    </div>

    {if $pickers_available}
        <div class="span7">
            {include file="common/subheader.tpl" title=$title}

            {include file="addons/mobile_app/components/inputs.tpl" input_name=$input_name inputs=$config_data.app_appearance.colors.$input_name}
        </div>
    {/if}
</div>
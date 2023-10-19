{strip}
    <div class="ty-top-panel">
        <div id="minimize_block" class="ty-top-panel__wrapper">
            <div class="ty-top-panel__header">
                <div class="ty-top-panel__logo">
                    {if $auth.user_type === "UserTypes::ADMIN"|enum}
                        <a href="{if "ULTIMATE"|fn_allowed_for}{$config.origin_http_location}/{/if}{$config.admin_index}" class="ty-top-panel__logo-link">
                    {/if}
                        {include_ext file="common/icon.tpl"
                            class="ty-icon-basket ty-top-panel__icon-basket"
                        }
                    {if $auth.user_type === "UserTypes::ADMIN"|enum}
                        </a>
                    {/if}
                </div>
                <h4 class="ty-top-panel__title">
                    {$title nofilter}
                </h4>
            </div>
            <div class="ty-top-panel-action">
                <span class="ty-top-panel-action_item">
                    <a href="{$href|fn_url}" class="ty-top-panel-btn cm-no-ajax cm-post">{__("close")}</a>
                </span>
            </div>
        </div>
    </div>
{/strip}

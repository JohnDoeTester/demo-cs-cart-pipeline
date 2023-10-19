{hook name="addons_detailed:sidebar_support"}
{if $addon.support || $addon.identified}
    <div class="sidebar-row">
        <h6>{__("addons.support")}</h6>

        {if $addon.identified}
            <div class="control-group">
                {include file="views/addons/components/support/contact_developer.tpl"}
            </div>
        {/if}

        {foreach $addon.support as $support}
            {if $support.url}
                <div class="control-group">
                    <p>
                        <a href="{$support.url}" target="_blank">
                            {$support.text nofilter}
                        </a>
                    </p>
                </div>
            {/if}
        {/foreach}

    </div>
{/if}
{/hook}
{capture name="mailing_lists"}
    {assign var="show_newsletters_content" value=false}
    <div class="subscription-container" id="subsciption_{$tab_id}">
        {foreach from=$page_mailing_lists item=list}
            {if $list.show_on_checkout}
                {assign var="show_newsletters_content" value=true}
            {/if}
            <input type="hidden" name="all_mailing_lists[]" value="{$list.list_id}"/>
            <div class="ty-newsletters__item{if !$list.show_on_checkout} hidden{/if}">
                <label for="fake_subscribe_list_{$list.list_id}">
                    <input type="checkbox"
                           id="subscribe_list_{$list.list_id}"
                           name="mailing_lists[]"
                           value="{$list.list_id}"
                           {if $user_mailing_lists[$list.list_id]}checked="checked"{/if}
                           class="checkbox cm-news-subscribe hidden"
                    />
                    <input type="checkbox"
                           id="fake_subscribe_list_{$list.list_id}"
                           data-ca-target-id="subscribe_list_{$list.list_id}"
                           value="{$list.list_id}"
                           {if $user_mailing_lists[$list.list_id]}checked="checked"{/if}
                           class="checkbox"
                           data-ca-lite-checkout-element="newsletter-toggler"
                    />
                    {$list.object}
                </label>
            </div>
        {/foreach}
        <!--subsciption_{$tab_id}--></div>
{/capture}

{if $show_newsletters_content}
    <div class="litecheckout__group litecheckout__newsletters">
        <div class="litecheckout__item litecheckout__item--full">
            <h2 class="litecheckout__step-title">{$block.name|default:__("text_signup_for_subscriptions")}</h2>
        </div>
        <div class="litecheckout__item litecheckout__item--full">
            {$smarty.capture.mailing_lists nofilter}
        </div>
    </div>
{/if}

<div id="content_gdpr_user_data">
    {$return_current_url = "profiles.update?user_id=`$user_id`"|escape:url}
    {include file="buttons/button.tpl" but_role="action" but_meta="cm-post" target="_blank" but_href="gdpr.export_to_xml?user_id={$user_id}" but_text=__("gdpr.export_to_xml")}
    {if !$anonymized}
        {btn type="text" href="profiles.anonymize?user_id={$user_id}&redirect_url={$return_current_url}" class="btn cm-confirm" data=["data-ca-confirm-text" => "{__("gdpr.text_anonymize_question")}"] text=__("gdpr.anonymize") method="POST"}
    {/if}
    <table class="table table-sort table-middle table--relative">
        <thead>
            <tr>
                <th>{__("user_info")}</th>
            </tr>
        </thead>
        {foreach $gdpr_user_data as $item_name => $user}
            <tr>
                <td>
                    <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_user_{$item_name}" class="cm-combination-carts"><span class="icon-caret-right"></span></span>
                    <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_user_{$item_name}" class="hidden cm-combination-carts"><span class="icon-caret-down"></span></span>
                    {__("gdpr_{$item_name}")}
                </td>
            </tr>
            <tbody id="user_{$item_name}" class="hidden row-more">
                <tr class="no-border">
                    <td colspan="3" class="row-more-body top row-gray">
                        <dl>
                            {foreach $user as $field_name => $field_values}
                                <dt><b>{$field_name}</b></dt>
                                <dd>
                                    {if $field_name == "orders_list"}
                                        {foreach $field_values as $order_id => $order_link}
                                            <a href="{$order_link}" target="_blank">#{$order_id} </a>
                                        {/foreach}
                                    {else}
                                        {implode(" | ", (array) $field_values)|default:"—" nofilter}
                                    {/if}
                                </dd>
                            {/foreach}
                        </dl>
                    </td>
                </tr>
            </tbody>
        {/foreach}
    </table>
<!--content_gdpr_user_data--></div>

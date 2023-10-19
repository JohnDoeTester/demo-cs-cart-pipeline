{*
    $id                         string          required            Object Id
    $dispatch                   string          required            Update status dispatch
    $disapprove_data            array                               Disapprove submit data
    $approve_data               array                               Approve submit data
    $disapprove_status          array                               Disapprove status
    $approve_status             array                               Approve status

    With disapprove and approve reasons
    ---
    $disapprove_reason_name     string                              Disapprove reason input name
    $approve_reason_name        string                              Approve reason input name
*}
{script src="js/tygh/backend/approve_disapprove.js"}

{if $header_view}
    {* Text button for header *}
    {$disapprove_btn_icon = "approve-disapprove__icon"}
    {$approve_btn_icon = "approve-disapprove__icon"}
    {$disapprove_btn_text = __("disapprove")}
    {$approve_btn_text = __("approve")}
    {$disapprove_btn_class = "btn approve-disapprove__btn approve-disapprove__btn--header-disapprove"}
    {$approve_btn_class = "btn approve-disapprove__btn approve-disapprove__btn--header-approve btn-primary"}
{else}
    {* Icon button for list *}
    {$disapprove_btn_icon = "approve-disapprove__icon icon-thumbs-down"}
    {$approve_btn_icon = "approve-disapprove__icon icon-thumbs-up"}
    {$disapprove_btn_text = ""}
    {$approve_btn_text = ""}
    {$disapprove_btn_class = "btn approve-disapprove__btn approve-disapprove__btn--list-disapprove"}
    {$approve_btn_class = "btn approve-disapprove__btn approve-disapprove__btn--list-approve"}
{/if}

{$disapprove_status = $disapprove_status|default:"D"}
{$approve_status = $approve_status|default:"A"}
{$disapprove_data = $disapprove_data|default:["data-ca-approve-disapprove-data" => {[
    "id" => $id,
    "status" => $disapprove_status,
    "notify_user" => "YesNo::YES"|enum
]|to_json nofilter}]}
{$approve_data = $approve_data|default:["data-ca-approve-disapprove-data" => {[
    "id" => $id,
    "status" => $approve_status,
    "notify_user" => "YesNo::YES"|enum
]|to_json nofilter}]}
{$disapprove_data["data-ca-approve-disapprove"] = "disapprove"}
{$approve_data["data-ca-approve-disapprove"] = "approve"}
{$return_url = $return_url|unescape:url|default:$config.current_url}

<div class="approve-disapprove"
    data-ca-approve-disapprove="container"
    data-ca-approve-disapprove-dispatch="{$dispatch}">
    {* Disapprove *}
    {if $disapprove_reason_name}
        {* Disapprove dropdown *}
        <div class="btn-group dropleft approve-disapprove__btn-group">
            <a class="dropdown-toggle {$disapprove_btn_class}" data-toggle="dropdown">
                {include_ext file="common/icon.tpl" class=$disapprove_btn_icon}
                {$disapprove_btn_text}
                <span class="caret"></span>
            </a>
            <ul class="dropdown-menu approve-disapprove__dropdown">
                <div class="approve-disapprove__content">
                    <textarea class="approve-disapprove__reason"
                        name="{$disapprove_reason_name}"
                        data-ca-approve-disapprove="disapprove_reason"
                        placeholder="{__("enter_disapproval_reason")}"
                    ></textarea>
                </div>
                <div class="approve-disapprove__footer">
                    {btn type="button"
                        id="`$dispatch`_`$id`_approve"
                        class="btn btn-primary approve-disapprove__btn"
                        text=__("disapprove")
                        data=$disapprove_data
                    }
                </div>
            </ul>
        </div>
    {else}
        {* Disapprove button *}
        {btn type="button"
            id="`$dispatch`_`$id`_approve"
            class=$disapprove_btn_class
            text=$disapprove_btn_text
            icon=$disapprove_btn_icon
            data=$disapprove_data
        }
    {/if}

    {* Approve *}
    {if $approve_reason_name}
        {* Approve dropdown *}
        <div class="btn-group dropleft approve-disapprove__btn-group">
            <a class="dropdown-toggle {$approve_btn_class}" data-toggle="dropdown">
                {include_ext file="common/icon.tpl" class=$approve_btn_icon}
                {$approve_btn_text}
                <span class="caret"></span>
            </a>
            <ul class="dropdown-menu approve-disapprove__dropdown">
                <div class="approve-disapprove__content">
                    <textarea class="approve-disapprove__reason"
                        name="{$approve_reason_name}"
                        placeholder="{__("type_comments_here")}"
                        data-ca-approve-disapprove="approve_reason"
                    ></textarea>
                </div>
                <div class="approve-disapprove__footer">
                    {btn type="button"
                        id="`$dispatch`_`$id`_approve"
                        class="btn btn-primary approve-disapprove__btn"
                        text=__("approve")
                        data=$approve_data
                    }
                </div>
            </ul>
        </div>
    {else}
        {btn type="button"
            id="`$dispatch`_`$id`_approve"
            class=$approve_btn_class
            text=$approve_btn_text
            icon=$approve_btn_icon
            data=$approve_data
        }
    {/if}
</div>

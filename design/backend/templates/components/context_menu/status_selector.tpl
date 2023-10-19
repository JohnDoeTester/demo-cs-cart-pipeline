{*
    $statuses array Statuses from context_menu schema
    $params   array Сontext menu component parameters
*}

<li class="btn bulk-edit__btn bulk-edit__btn--check-items">
    <input class="bulk-edit__btn-content--checkbox hidden bulkedit-disabler"
           type="checkbox"
           checked
           data-ca-bulkedit-enable="[data-ca-bulkedit-default-object=true]"
           data-ca-bulkedit-disable="[data-ca-bulkedit-expanded-object=true]"
    />
    <span class="bulk-edit__btn-content dropdown-toggle" data-toggle="dropdown">
        <span data-ca-longtap-selected-counter="true">0</span> <span class="mobile-hide">{__("selected")}</span> <span class="caret mobile-hide"></span>
    </span>

    {include file="common/check_items.tpl"
        dropdown_menu_class="cm-check-items"
        wrap_select_actions_into_dropdown=true
        check_statuses=$statuses
        is_check_all_shown=$params.is_check_all_shown|default:false
        elms_container=$elms_container
    }
</li>
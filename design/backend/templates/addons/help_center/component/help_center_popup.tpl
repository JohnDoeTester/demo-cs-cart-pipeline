{$help_center_counter = $help_center_counter|default:""}

<button type="button"
    class="cm-dialog-opener help-center-popup__btn"
    id="opener_help_center_popup" 
    title="{__("help_center.growth_center")}"
    data-ca-target-id="content_help_center_popup"
    data-ca-max-width="1120"
    data-ca-help-center="popupBtn"
>
    {include_ext file="common/icon.tpl"
        class="help-center-popup__icon icon-question-sign"
        title=__("help_center.growth_center")
        data=["data-ca-help-center-counter" => $help_center_counter]
    }
</button>
<div class="hidden help-center-popup" data-ca-help-center="popupContent" id="content_help_center_popup">
    {include file="addons/help_center/views/help_center/manage.tpl"
        in_popup=true
    }
</div>

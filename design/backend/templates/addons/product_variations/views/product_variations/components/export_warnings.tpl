<div>
     <div class="table-responsive-wrapper">
        <table width="100%" class="table table-no-hover table--relative table-responsive table-responsive-w-titles">
            {foreach $messages as $message}
                <tr class="no-border">
                    <td data-th="&nbsp;">{$message}</td>
                </tr>
            {/foreach}
        </table>
    </div>
    <div>
        <a class="btn cm-notification-close pull-right">{__("close")}</a>
    </div>
</div>
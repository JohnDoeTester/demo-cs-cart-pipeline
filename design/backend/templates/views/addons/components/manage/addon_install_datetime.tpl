{$show_install_datetime = $show_install_datetime|default:true}
{if $show_install_datetime}
    {* Hidden date for sorting *}
    <div class="hidden">{$a.install_datetime}</div>
{/if}

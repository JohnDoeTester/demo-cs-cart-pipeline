{$date_format = ($settings.Appearance.calendar_date_format === "month_first") ? "%m/%d/%Y" : "%d/%m/%Y"}
{$current_year = $smarty.now|date_format:"%Y"}

{$is_changeable_range = $is_changeable_range|default:true}

{$start_range = ($is_changeable_range) ? "c-100" : "-100"}
{$end_range = ($is_changeable_range) ? "c+10" : $current_year}

{$start_year_range = $start_year_range|default:$start_range}
{$end_year_range = $end_year_range|default:$end_range}

<div class="ty-calendar__block">
    <input type="text" id="{$date_id}" name="{$date_name}" class="ty-calendar__input{if $date_meta} {$date_meta}{/if} cm-calendar" value="{if $date_val}{$date_val|date_format:"`$date_format`"}{/if}" {$extra} size="10" autocomplete="disabled" />
    <a class="cm-external-focus ty-calendar__link" data-ca-external-focus-id="{$date_id}">
        {include_ext file="common/icon.tpl"
            class="ty-icon-calendar ty-calendar__button"
            title=__("calendar")
        }
    </a>
    {* autocomplete="off" for Chrome *}
    <input type="text" hidden disabled name="fake_mail" aria-hidden="true">
</div>

<script>
(function(_, $) {$ldelim}
    $.ceEvent('on', 'ce.commoninit', function(context) {

        $('#{$date_id}').datepicker({
            changeMonth: true,
            duration: 'fast',
            changeYear: true,
            numberOfMonths: 1,
            selectOtherMonths: true,
            showOtherMonths: true,

            firstDay: {if $settings.Appearance.calendar_week_format == "sunday_first"}0{else}1{/if},
            dayNamesMin: ['{__("weekday_abr_0")}', '{__("weekday_abr_1")}', '{__("weekday_abr_2")}', '{__("weekday_abr_3")}', '{__("weekday_abr_4")}', '{__("weekday_abr_5")}', '{__("weekday_abr_6")}'],
            monthNamesShort: ['{__("month_name_abr_1")|escape:"html"}', '{__("month_name_abr_2")|escape:"html"}', '{__("month_name_abr_3")|escape:"html"}', '{__("month_name_abr_4")|escape:"html"}', '{__("month_name_abr_5")|escape:"html"}', '{__("month_name_abr_6")|escape:"html"}', '{__("month_name_abr_7")|escape:"html"}', '{__("month_name_abr_8")|escape:"html"}', '{__("month_name_abr_9")|escape:"html"}', '{__("month_name_abr_10")|escape:"html"}', '{__("month_name_abr_11")|escape:"html"}', '{__("month_name_abr_12")|escape:"html"}'],
            yearRange: '{$start_year_range}:{$end_year_range}',
            dateFormat: '{if $settings.Appearance.calendar_date_format == "month_first"}mm/dd/yy{else}dd/mm/yy{/if}'
        });
    });
{$rdelim}(Tygh, Tygh.$));
</script>

<div id="{$option_id}_dialog" class="hidden"></div>
<input id="{$option_id}"
       class="input-large"
       type="text"
       name="{$field_name_prefix}[{$option_id}]"
       value="{$option.selected_value|default:$option.default_value}"
/>
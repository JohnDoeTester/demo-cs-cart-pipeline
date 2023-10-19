<div class="switch-mini ty-switch-checkbox cm-switch-checkbox list-btns {if $meta}{$meta}{/if}"
     {if $extra_attrs}
         {$extra_attrs|render_tag_attrs nofilter}
     {/if}
     {if $id}
         id="{$id}"
     {/if}
>
    <input type="checkbox"
           {if $checked}
               checked="checked"
           {/if}
           {if $input_name}
               name="{$input_name}"
           {/if}
           {if $input_value}
               value="{$input_value}"
           {/if}
           {if $input_id}
               id="{$input_id}"
           {/if}
           {if $input_attrs}
               {$input_attrs|render_tag_attrs nofilter}
           {/if}
           {if $input_class}
               class="{$input_class}"
           {/if}
           {if $input_readonly}
               readonly
           {/if}
           {if $input_disabled}
               disabled
           {/if}
    />
</div>

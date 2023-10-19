
{if $field.field_name == 'b_city' }

            <script  class="cm-ajax-force">
            //<![CDATA[

                    Tygh.$("[name='user_data[b_city]']").autocomplete({$ldelim}
                        source: function( request, response ) {$ldelim}

                            var check_country;
                            var check_state;
                            check_country = $("[name='user_data[b_country]']").length ? $("[name='user_data[b_country]']").val() : '';
                            check_state = $("[name='user_data[b_state]']").length ? $("[name='user_data[b_state]']").val() : '';

                            $.ceAjax('request', fn_url('city.autocomplete_city?q=' + request.term + '&check_state=' + check_state + '&check_country=' + check_country), {$ldelim}
                                callback: function(data) {$ldelim}
                                    response(data.autocomplete);
                                {$rdelim}
                            {$rdelim});
                        {$rdelim}
                    {$rdelim});

            //]]>
            </script>

{/if}
{if $field.field_name == 's_city'}

            <script  class="cm-ajax-force">
            //<![CDATA[

                    Tygh.$("[name='user_data[s_city]']").autocomplete({$ldelim}
                        source: function( request, response ) {$ldelim}

                            var check_country;
                            var check_state;
                            check_country = $("[name='user_data[s_country]']").length ? $("[name='user_data[s_country]']").val() : '';
                            check_state = $("[name='user_data[s_state]']").length ? $("[name='user_data[s_state]']").val() : '';

                            $.ceAjax('request', fn_url('city.autocomplete_city?q=' + request.term + '&check_state=' + check_state + '&check_country=' + check_country), {$ldelim}
                                callback: function(data) {$ldelim}
                                    response(data.autocomplete);
                                {$rdelim}
                            {$rdelim});
                        {$rdelim}
                    {$rdelim});

            //]]>
            </script>

{/if}

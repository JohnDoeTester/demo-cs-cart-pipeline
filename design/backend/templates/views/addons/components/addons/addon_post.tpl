{if $message}
    {$NAME_CHARACTERS_THRESHOLD = 30}
    {$VISIBLE_COMMENT_LINES = 7}
    {$customer_name = $user_data.name|truncate:$NAME_CHARACTERS_THRESHOLD:"...":true}
    {$date_machine_format = "%Y-%m-%dT%H:%M:%S"}
    {$date_time_machine_format = "`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}

    <section>

        <section>

            <header class="flex flex-wrap spaced-child">

                {* Review stars *}
                {include file="views/addons/components/rating/stars.tpl"
                    rating=$rating_value
                }

            </header>

            {* Message *}
            {include file="common/content_more.tpl"
                text=$message
                visible_comment_lines=$VISIBLE_COMMENT_LINES
            }

        </section>

        <footer>
            <small class="dashed-child">

                {* Review author *}
                <span title="{$user_data.name}">
                    {$customer_name nofilter}
                </span>

                {* Review date *}
                <span>
                    <time datetime="{$review.timestamp|date_format:$date_machine_format}"
                        title="{$review.timestamp|date_format:$date_time_machine_format}"
                    >
                        {$timestamp|date_format:$settings.Appearance.date_format}
                    </time>
                </span>

            </small>
        </footer>

    </section>
{/if}

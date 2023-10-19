{if $user.user_type == "C" && $user.anonymized != "Y"}
    <li>{btn type="list" text=__("gdpr.anonymize") href="profiles.anonymize?user_id=`$user.user_id`&redirect_url=`$return_current_url`" class="cm-confirm" data=["data-ca-confirm-text" => "{__("gdpr.text_anonymize_question")}"] method="POST"}</li>
{/if}

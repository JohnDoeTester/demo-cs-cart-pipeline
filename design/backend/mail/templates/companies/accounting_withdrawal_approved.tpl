{include file="common/letter_header.tpl"}

{__("vendor_payouts.withdrawal_approved_text", ["[amount]" => $payment.amount, "[date]" => $payment.date]) nofilter}.

{include file="common/letter_footer.tpl"}
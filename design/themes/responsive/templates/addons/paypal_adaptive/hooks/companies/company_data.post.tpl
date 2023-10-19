{if !empty($company.paypal_verification_status.main_pair)}
    {include file="common/image.tpl" image_width=$company.paypal_verification_status.width image_height=$company.paypal_verification_status.height obj_id=$object_id images=$company.paypal_verification_status.main_pair class="vendor-catalog-verification"}
{elseif !empty($company.paypal_verification_status.verified) && $company.paypal_verification_status.verified == 'verified'}
    <span class="vendor-catalog-verification">&nbsp;{__('verified_by_paypal')}</span>
{/if}
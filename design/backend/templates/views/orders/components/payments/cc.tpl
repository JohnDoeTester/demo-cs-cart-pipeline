{script src="js/lib/inputmask/jquery.inputmask.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

{$card_item = $cart.payment_info|fn_filter_card_data}
{$required = $required|default:"cm-required"}

<div class="clearfix">
    <div class="credit-card">
            <div class="control-group">
                <label for="cc_number{$id_suffix}" class="control-label cm-cc-number cm-autocomplete-off {$required}" {if $enable_required}data-ca-default-required="1"{/if}>{__("card_number")}</label>
                <div class="controls">
                    <input id="cc_number{$id_suffix}" size="35" type="text" name="payment_info[card_number]" value="{$card_item.card_number}" class="input-big" />
                </div>
                <ul class="cc-icons-wrap cc-icons unstyled" id="cc_icons{$id_suffix}">
                    <li class="cc-icon cm-cc-default"><span class="default">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-visa"><span class="visa">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-visa_electron"><span class="visa-electron">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-mastercard"><span class="mastercard">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-maestro"><span class="maestro">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-amex"><span class="american-express">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-discover"><span class="discover">&nbsp;</span></li>
                </ul>

            </div>

            <div class="control-group">
                <label for="cc_exp_month{$id_suffix}" class="control-label cm-cc-date {$required}">{__("valid_thru")}</label>
                <div class="controls clear">
                    <div class="cm-field-container nowrap">
                        <input type="text" id="cc_exp_month{$id_suffix}" name="payment_info[expiry_month]" value="{$card_item.expiry_month}" size="2" maxlength="2" class="input-small" />&nbsp;/&nbsp;<input type="text" id="cc_exp_year{$id_suffix}" name="payment_info[expiry_year]" value="{$card_item.expiry_year}" size="2" maxlength="2" class="input-small" />
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label for="cc_name{$id_suffix}" class="control-label {$required}">{__("cardholder_name")}</label>
                <div class="controls">
                    <input id="cc_name{$id_suffix}" size="35" type="text" name="payment_info[cardholder_name]" value="{$card_item.cardholder_name}" class="input-text uppercase" />
                </div>
            </div>
    </div>

    <div class="control-group cvv-field">
        <label for="cc_cvv2{$id_suffix}" class="control-label cm-cc-cvv2 cm-integer cm-autocomplete-off {$required}">{__("cvv2")}</label>
        <div class="controls">
        <input id="cc_cvv2{$id_suffix}" type="text" name="payment_info[cvv2]" value="{$card_item.cvv2}" size="4" maxlength="4"/>

        <div class="cvv2">
            <a>{__("what_is_cvv2")}</a>
            <div class="popover fade bottom in">
                <div class="arrow"></div>
                <h3 class="popover-title">{__("what_is_cvv2")}</h3>
                <div class="popover-content">
                    <div class="cvv2-note">
                            <div class="card-info clearfix">
                                <div class="cards-images">
                                    <img src="{$images_dir}/visa_cvv.png" border="0" alt="" />
                                </div>
                                <div class="cards-description">
                                    <strong>{__("visa_card_discover")}</strong>
                                    <p>{__("credit_card_info")}</p>
                                </div>
                            </div>
                            <div class="card-info ax clearfix">
                                <div class="cards-images">
                                    <img src="{$images_dir}/express_cvv.png" border="0" alt="" />
                                </div>
                                <div class="cards-description">
                                    <strong>{__("american_express")}</strong>
                                    <p>{__("american_express_info")}</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(_, $) {
    $.ceEvent('on', 'ce.commoninit', function() {

        var icons = $('#cc_icons{$id_suffix} li');
        var ccNumber = $('label[for=cc_number{$id_suffix}]');
        var ccNumberInput = $("#cc_number{$id_suffix}");
        var ccCv2 = $('label[for=cc_cvv2{$id_suffix}]');
        var ccCv2Input = $("#cc_cvv2{$id_suffix}");
        var ccMonthInput = $("#cc_exp_month{$id_suffix}");
        var ccYearInput = $("#cc_exp_year{$id_suffix}");

        if (jQuery.isEmptyObject(ccNumberInput.data('_inputmask'))) {

            ccNumberInput.inputmask({
                mask: '9999 9999 9999 9[9][9][9][9][9][9]',
                placeholder: ''
            });

            ccMonthInput.inputmask({
                mask: '9[9]',
                placeholder: ''
            });

            ccYearInput.inputmask({
                mask: '99[99]',
                placeholder: ''
            });

            ccCv2Input.inputmask({
                mask: '999[9]',
                placeholder: ''
            });

            $.ceFormValidator('registerValidator', {
                class_name: 'cm-cc-number',
                message: '',
                func: function (id) {
                    ccNumberInput = $('#' + id);
                    return $.is.blank(ccNumberInput.val()) || ccNumberInput.inputmask('isComplete');
                }
            });

            $.ceFormValidator('registerValidator', {
                class_name: 'cm-cc-cvv2',
                message: '{__("error_validator_ccv")|escape:javascript}',
                func: function (id) {
                    ccCv2Input = $('#' + id);
                    return $.is.blank(ccCv2Input.val()) || ccNumberInput.inputmask('isComplete');
                }
            });
        }

        if (ccNumberInput.length) {
            ccNumberInput.validateCreditCard(function (result) {
                icons.removeClass('active');
                if (result.card_type) {
                    icons.filter('.cm-cc-' + result.card_type.name).addClass('active');

                    if (!ccNumber.data('caDefaultRequired')) {
                        return;
                    }

                    if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) !== -1) {
                        ccCv2.removeClass("cm-required");
                    } else {
                        ccCv2.addClass("cm-required");
                    }
                }
            });
        }


    });
})(Tygh, Tygh.$);
</script>

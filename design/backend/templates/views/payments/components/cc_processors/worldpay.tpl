{assign var="return_url" value="http"|fn_payment_url:"worldpay.php"}
<p>{__("text_worldpay_notice", ["[return_url]" => $return_url]) nofilter}</p>
<hr>

<div class="control-group">
    <label class="control-label" for="account_id">{__("installation_id")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][account_id]" id="account_id" value="{$processor_params.account_id}"   size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="callback_password">{__("payment_response_password")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][callback_password]" id="callback_password" value="{$processor_params.callback_password}"   size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="md5_secret">{__("worldpay_secret")}:</label>
   <div class="controls">
        <input type="text" name="payment_data[processor_params][md5_secret]" id="md5_secret" value="{$processor_params.md5_secret}"   size="60">
   </div>
</div>

<div class="control-group">
    <label class="control-label" for="test">{__("test_live_mode")}:</label>
   <div class="controls">
        <select name="payment_data[processor_params][test]" id="test">
           <option value="101" {if $processor_params.test == "101"}selected="selected"{/if}>{__("test")}: {__("declined")}</option>
           <option value="100" {if $processor_params.test == "100"}selected="selected"{/if}>{__("test")}: {__("approved")}</option>
           <option value="0" {if $processor_params.test == "0"}selected="selected"{/if}>{__("live")}</option>
       </select>
   </div>
</div>

<div class="control-group">
    <label class="control-label" for="currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">	    
	    <option value="AFN" {if $processor_params.currency == "AFN"}selected="selected"{/if}>{__("currency_code_afn")}</option>
	    <option value="DZD" {if $processor_params.currency == "DZD"}selected="selected"{/if}>{__("currency_code_dzd")}</option>
	    <option value="AOA" {if $processor_params.currency == "AOA"}selected="selected"{/if}>{__("currency_code_aoa")}</option>
	    <option value="AON" {if $processor_params.currency == "AON"}selected="selected"{/if}>{__("currency_code_aon")}</option>
	    <option value="ANG" {if $processor_params.currency == "ANG"}selected="selected"{/if}>{__("currency_code_ang")}</option>
	    <option value="AWG" {if $processor_params.currency == "AWG"}selected="selected"{/if}>{__("currency_code_awg")}</option>
	    <option value="AUD" {if $processor_params.currency == "AUD"}selected="selected"{/if}>{__("currency_code_aud")}</option>
	    <option value="ATS" {if $processor_params.currency == "ATS"}selected="selected"{/if}>{__("currency_code_ats")}</option>
	    <option value="AZN" {if $processor_params.currency == "AZN"}selected="selected"{/if}>{__("currency_code_azn")}</option>
	    <option value="BSD" {if $processor_params.currency == "BSD"}selected="selected"{/if}>{__("currency_code_bsd")}</option>
	    <option value="BHD" {if $processor_params.currency == "BHD"}selected="selected"{/if}>{__("currency_code_bhd")}</option>
	    <option value="BDT" {if $processor_params.currency == "BDT"}selected="selected"{/if}>{__("currency_code_bdt")}</option>
	    <option value="BBD" {if $processor_params.currency == "BBD"}selected="selected"{/if}>{__("currency_code_bbd")}</option>
	    <option value="BYN" {if $processor_params.currency == "BYN"}selected="selected"{/if}>{__("currency_code_byn")}</option>
	    <option value="BEF" {if $processor_params.currency == "BEF"}selected="selected"{/if}>{__("currency_code_bef")}</option>
	    <option value="BZD" {if $processor_params.currency == "BZD"}selected="selected"{/if}>{__("currency_code_bzd")}</option>
	    <option value="BMD" {if $processor_params.currency == "BMD"}selected="selected"{/if}>{__("currency_code_bmd")}</option>
	    <option value="BTN" {if $processor_params.currency == "BTN"}selected="selected"{/if}>{__("currency_code_btn")}</option>
	    <option value="BOB" {if $processor_params.currency == "BOB"}selected="selected"{/if}>{__("currency_code_bob")}</option>
	    <option value="BAM" {if $processor_params.currency == "BAM"}selected="selected"{/if}>{__("currency_code_bam")}</option>
	    <option value="BWP" {if $processor_params.currency == "BWP"}selected="selected"{/if}>{__("currency_code_bwp")}</option>
	    <option value="BRL" {if $processor_params.currency == "BRL"}selected="selected"{/if}>{__("currency_code_brl")}</option>
	    <option value="BDN" {if $processor_params.currency == "BND"}selected="selected"{/if}>{__("currency_code_bnd")}</option>
	    <option value="BGL" {if $processor_params.currency == "BGL"}selected="selected"{/if}>{__("currency_code_bgl")}</option>
	    <option value="BIF" {if $processor_params.currency == "BIF"}selected="selected"{/if}>{__("currency_code_bif")}</option>
	    <option value="KHR" {if $processor_params.currency == "KHR"}selected="selected"{/if}>{__("currency_code_khr")}</option>
	    <option value="CAD" {if $processor_params.currency == "CAD"}selected="selected"{/if}>{__("currency_code_cad")}</option>
	    <option value="CVE" {if $processor_params.currency == "CVE"}selected="selected"{/if}>{__("currency_code_cve")}</option>
	    <option value="KYD" {if $processor_params.currency == "KYD"}selected="selected"{/if}>{__("currency_code_kyd")}</option>
	    <option value="XOF" {if $processor_params.currency == "XOF"}selected="selected"{/if}>{__("currency_code_xof")}</option>
	    <option value="XAF" {if $processor_params.currency == "XAF"}selected="selected"{/if}>{__("currency_code_xaf")}</option>
	    <option value="XPF" {if $processor_params.currency == "XPF"}selected="selected"{/if}>{__("currency_code_xpf")}</option>
	    <option value="CLP" {if $processor_params.currency == "CLP"}selected="selected"{/if}>{__("currency_code_clp")}</option>
	    <option value="COP" {if $processor_params.currency == "COP"}selected="selected"{/if}>{__("currency_code_cop")}</option>
	    <option value="KMF" {if $processor_params.currency == "KMF"}selected="selected"{/if}>{__("currency_code_kmf")}</option>
	    <option value="CDF" {if $processor_params.currency == "CDF"}selected="selected"{/if}>{__("currency_code_cdf")}</option>
	    <option value="CRC" {if $processor_params.currency == "CRC"}selected="selected"{/if}>{__("currency_code_crc")}</option>
	    <option value="HRK" {if $processor_params.currency == "HRK"}selected="selected"{/if}>{__("currency_code_hrk")}</option>
	    <option value="CYP" {if $processor_params.currency == "CYP"}selected="selected"{/if}>{__("currency_code_cyp")}</option>
	    <option value="CZK" {if $processor_params.currency == "CZK"}selected="selected"{/if}>{__("currency_code_czk")}</option>
	    <option value="DKK" {if $processor_params.currency == "DKK"}selected="selected"{/if}>{__("currency_code_dkk")}</option>
	    <option value="DEM" {if $processor_params.currency == "DEM"}selected="selected"{/if}>{__("currency_code_dem")}</option>
	    <option value="DJF" {if $processor_params.currency == "DJF"}selected="selected"{/if}>{__("currency_code_djf")}</option>
	    <option value="STD" {if $processor_params.currency == "STD"}selected="selected"{/if}>{__("currency_code_std")}</option>
	    <option value="NLG" {if $processor_params.currency == "NLG"}selected="selected"{/if}>{__("currency_code_nlg")}</option>
	    <option value="DOP" {if $processor_params.currency == "DOP"}selected="selected"{/if}>{__("currency_code_dop")}</option>
	    <option value="XCD" {if $processor_params.currency == "XCD"}selected="selected"{/if}>{__("currency_code_xcd")}</option>
	    <option value="ECS" {if $processor_params.currency == "ECS"}selected="selected"{/if}>{__("currency_code_ecs")}</option>
	    <option value="EGP" {if $processor_params.currency == "EGP"}selected="selected"{/if}>{__("currency_code_egp")}</option>
	    <option value="SVC" {if $processor_params.currency == "SVC"}selected="selected"{/if}>{__("currency_code_svc")}</option>
	    <option value="ERN" {if $processor_params.currency == "ERN"}selected="selected"{/if}>{__("currency_code_ern")}</option>
	    <option value="RUB" {if $processor_params.currency == "RUB"}selected="selected"{/if}>{__("currency_code_rub")}</option>
	    <option value="EEK" {if $processor_params.currency == "EEK"}selected="selected"{/if}>{__("currency_code_eek")}</option>
	    <option value="ETB" {if $processor_params.currency == "ETB"}selected="selected"{/if}>{__("currency_code_etb")}</option>
	    <option value="EUR" {if $processor_params.currency == "EUR"}selected="selected"{/if}>{__("currency_code_eur")}</option>
	    <option value="FKP" {if $processor_params.currency == "FKP"}selected="selected"{/if}>{__("currency_code_fkp")}</option>
	    <option value="FJD" {if $processor_params.currency == "FJD"}selected="selected"{/if}>{__("currency_code_fjd")}</option>
	    <option value="FIM" {if $processor_params.currency == "FIM"}selected="selected"{/if}>{__("currency_code_fim")}</option>
	    <option value="FRF" {if $processor_params.currency == "FRF"}selected="selected"{/if}>{__("currency_code_frf")}</option>
	    <option value="GMD" {if $processor_params.currency == "GMD"}selected="selected"{/if}>{__("currency_code_gmd")}</option>
	    <option value="GEL" {if $processor_params.currency == "GEL"}selected="selected"{/if}>{__("currency_code_gel")}</option>
	    <option value="GHS" {if $processor_params.currency == "GHS"}selected="selected"{/if}>{__("currency_code_ghs")}</option>
	    <option value="GIP" {if $processor_params.currency == "GIP"}selected="selected"{/if}>{__("currency_code_gip")}</option>
	    <option value="GRD" {if $processor_params.currency == "GRD"}selected="selected"{/if}>{__("currency_code_grd")}</option>
	    <option value="GTQ" {if $processor_params.currency == "GTQ"}selected="selected"{/if}>{__("currency_code_gtq")}</option>
	    <option value="GNF" {if $processor_params.currency == "GNF"}selected="selected"{/if}>{__("currency_code_gnf")}</option>
	    <option value="GYD" {if $processor_params.currency == "GYD"}selected="selected"{/if}>{__("currency_code_gyd")}</option>
	    <option value="HTG" {if $processor_params.currency == "HTG"}selected="selected"{/if}>{__("currency_code_htg")}</option>
	    <option value="HNL" {if $processor_params.currency == "HNL"}selected="selected"{/if}>{__("currency_code_hnl")}</option>
	    <option value="HKD" {if $processor_params.currency == "HKD"}selected="selected"{/if}>{__("currency_code_hkd")}</option>
	    <option value="HUF" {if $processor_params.currency == "HUF"}selected="selected"{/if}>{__("currency_code_huf")}</option>
	    <option value="ISK" {if $processor_params.currency == "ISK"}selected="selected"{/if}>{__("currency_code_isk")}</option>
	    <option value="INR" {if $processor_params.currency == "INR"}selected="selected"{/if}>{__("currency_code_inr")}</option>
	    <option value="IDR" {if $processor_params.currency == "IDR"}selected="selected"{/if}>{__("currency_code_idr")}</option>
	    <option value="IRR" {if $processor_params.currency == "IRR"}selected="selected"{/if}>{__("currency_code_irr")}</option>
	    <option value="IQD" {if $processor_params.currency == "IQD"}selected="selected"{/if}>{__("currency_code_iqd")}</option>
	    <option value="IEP" {if $processor_params.currency == "IEP"}selected="selected"{/if}>{__("currency_code_iep")}</option>
	    <option value="ITL" {if $processor_params.currency == "ITL"}selected="selected"{/if}>{__("currency_code_itl")}</option>
	    <option value="JMD" {if $processor_params.currency == "JMD"}selected="selected"{/if}>{__("currency_code_jmd")}</option>
	    <option value="JPY" {if $processor_params.currency == "JPY"}selected="selected"{/if}>{__("currency_code_jpy")}</option>
	    <option value="JOD" {if $processor_params.currency == "JOD"}selected="selected"{/if}>{__("currency_code_jod")}</option>
	    <option value="KZT" {if $processor_params.currency == "KZT"}selected="selected"{/if}>{__("currency_code_kzt")}</option>
	    <option value="KES" {if $processor_params.currency == "KES"}selected="selected"{/if}>{__("currency_code_kes")}</option>
	    <option value="KWD" {if $processor_params.currency == "KWD"}selected="selected"{/if}>{__("currency_code_kwd")}</option>
	    <option value="LAK" {if $processor_params.currency == "LAK"}selected="selected"{/if}>{__("currency_code_lak")}</option>
	    <option value="LVL" {if $processor_params.currency == "LVL"}selected="selected"{/if}>{__("currency_code_lvl")}</option>
	    <option value="LBP" {if $processor_params.currency == "LBP"}selected="selected"{/if}>{__("currency_code_lbp")}</option>
	    <option value="LSL" {if $processor_params.currency == "LSL"}selected="selected"{/if}>{__("currency_code_lsl")}</option>
	    <option value="LRD" {if $processor_params.currency == "LRD"}selected="selected"{/if}>{__("currency_code_lrd")}</option>
	    <option value="LYD" {if $processor_params.currency == "LYD"}selected="selected"{/if}>{__("currency_code_lyd")}</option>
	    <option value="LTL" {if $processor_params.currency == "LTL"}selected="selected"{/if}>{__("currency_code_ltl")}</option>
	    <option value="LUF" {if $processor_params.currency == "LUF"}selected="selected"{/if}>{__("currency_code_luf")}</option>
	    <option value="MOP" {if $processor_params.currency == "MOP"}selected="selected"{/if}>{__("currency_code_mop")}</option>
	    <option value="MKD" {if $processor_params.currency == "MKD"}selected="selected"{/if}>{__("currency_code_mkd")}</option>
	    <option value="MGA" {if $processor_params.currency == "MGA"}selected="selected"{/if}>{__("currency_code_mga")}</option>
	    <option value="MGF" {if $processor_params.currency == "MGF"}selected="selected"{/if}>{__("currency_code_mgf")}</option>
	    <option value="MWK" {if $processor_params.currency == "MWK"}selected="selected"{/if}>{__("currency_code_mwk")}</option>
	    <option value="MYR" {if $processor_params.currency == "MYR"}selected="selected"{/if}>{__("currency_code_myr")}</option>
	    <option value="MVR" {if $processor_params.currency == "MVR"}selected="selected"{/if}>{__("currency_code_mvr")}</option>
	    <option value="MTL" {if $processor_params.currency == "MTL"}selected="selected"{/if}>{__("currency_code_mtl")}</option>
	    <option value="MRO" {if $processor_params.currency == "MRO"}selected="selected"{/if}>{__("currency_code_mro")}</option>
	    <option value="MUR" {if $processor_params.currency == "MUR"}selected="selected"{/if}>{__("currency_code_mur")}</option>
	    <option value="MXN" {if $processor_params.currency == "MXN"}selected="selected"{/if}>{__("currency_code_mxn")}</option>
	    <option value="MDL" {if $processor_params.currency == "MDL"}selected="selected"{/if}>{__("currency_code_mdl")}</option>
	    <option value="MNT" {if $processor_params.currency == "MNT"}selected="selected"{/if}>{__("currency_code_mnt")}</option>
	    <option value="MAD" {if $processor_params.currency == "MAD"}selected="selected"{/if}>{__("currency_code_mad")}</option>
	    <option value="MZN" {if $processor_params.currency == "MZN"}selected="selected"{/if}>{__("currency_code_mzn")}</option>
	    <option value="MMK" {if $processor_params.currency == "MMK"}selected="selected"{/if}>{__("currency_code_mmk")}</option>
	    <option value="NAD" {if $processor_params.currency == "NAD"}selected="selected"{/if}>{__("currency_code_nad")}</option>
	    <option value="NPR" {if $processor_params.currency == "NPR"}selected="selected"{/if}>{__("currency_code_npr")}</option>
	    <option value="BGN" {if $processor_params.currency == "BGN"}selected="selected"{/if}>{__("currency_code_bgn")}</option>
	    <option value="ILS" {if $processor_params.currency == "ILS"}selected="selected"{/if}>{__("currency_code_ils")}</option>
	    <option value="RON" {if $processor_params.currency == "RON"}selected="selected"{/if}>{__("currency_code_ron")}</option>
	    <option value="RSD" {if $processor_params.currency == "RSD"}selected="selected"{/if}>{__("currency_code_rsd")}</option>
	    <option value="TWD" {if $processor_params.currency == "TWD"}selected="selected"{/if}>{__("currency_code_twd")}</option>
	    <option value="TRY" {if $processor_params.currency == "TRY"}selected="selected"{/if}>{__("currency_code_try")}</option>
	    <option value="NZD" {if $processor_params.currency == "NZD"}selected="selected"{/if}>{__("currency_code_nzd")}</option>
	    <option value="PLN" {if $processor_params.currency == "PLN"}selected="selected"{/if}>{__("currency_code_pln")}</option>
	    <option value="NIO" {if $processor_params.currency == "NIO"}selected="selected"{/if}>{__("currency_code_nio")}</option>
	    <option value="NGN" {if $processor_params.currency == "NGN"}selected="selected"{/if}>{__("currency_code_ngn")}</option>
	    <option value="KPW" {if $processor_params.currency == "KPW"}selected="selected"{/if}>{__("currency_code_kpw")}</option>
	    <option value="NOK" {if $processor_params.currency == "NOK"}selected="selected"{/if}>{__("currency_code_nok")}</option>
	    <option value="ARS" {if $processor_params.currency == "ARS"}selected="selected"{/if}>{__("currency_code_ars")}</option>
	    <option value="PKR" {if $processor_params.currency == "PKR"}selected="selected"{/if}>{__("currency_code_pkr")}</option>
	    <option value="PAB" {if $processor_params.currency == "PAB"}selected="selected"{/if}>{__("currency_code_pab")}</option>
	    <option value="PGK" {if $processor_params.currency == "PGK"}selected="selected"{/if}>{__("currency_code_pgk")}</option>
	    <option value="PYG" {if $processor_params.currency == "PYG"}selected="selected"{/if}>{__("currency_code_pyg")}</option>
	    <option value="PEN" {if $processor_params.currency == "PEN"}selected="selected"{/if}>{__("currency_code_pen")}</option>
	    <option value="UYU" {if $processor_params.currency == "UYU"}selected="selected"{/if}>{__("currency_code_uyu")}</option>
	    <option value="PTE" {if $processor_params.currency == "PTE"}selected="selected"{/if}>{__("currency_code_pte")}</option>
	    <option value="QAR" {if $processor_params.currency == "QAR"}selected="selected"{/if}>{__("currency_code_qar")}</option>
	    <option value="OMR" {if $processor_params.currency == "OMR"}selected="selected"{/if}>{__("currency_code_omr")}</option>
	    <option value="ROL" {if $processor_params.currency == "ROL"}selected="selected"{/if}>{__("currency_code_rol")}</option>
	    <option value="RWF" {if $processor_params.currency == "RWF"}selected="selected"{/if}>{__("currency_code_rwf")}</option>
	    <option value="WST" {if $processor_params.currency == "WST"}selected="selected"{/if}>{__("currency_code_wst")}</option>
	    <option value="SAR" {if $processor_params.currency == "SAR"}selected="selected"{/if}>{__("currency_code_sar")}</option>
	    <option value="CSD" {if $processor_params.currency == "CSD"}selected="selected"{/if}>{__("currency_code_csd")}</option>
	    <option value="GBP" {if $processor_params.currency == "GBP"}selected="selected"{/if}>{__("currency_code_gbp")}</option>
	    <option value="SCR" {if $processor_params.currency == "SCR"}selected="selected"{/if}>{__("currency_code_scr")}</option>
	    <option value="SLL" {if $processor_params.currency == "SLL"}selected="selected"{/if}>{__("currency_code_sll")}</option>
	    <option value="SGD" {if $processor_params.currency == "SGD"}selected="selected"{/if}>{__("currency_code_sgd")}</option>
	    <option value="SKK" {if $processor_params.currency == "SKK"}selected="selected"{/if}>{__("currency_code_skk")}</option>
	    <option value="SIT" {if $processor_params.currency == "SIT"}selected="selected"{/if}>{__("currency_code_sit")}</option>
	    <option value="SBD" {if $processor_params.currency == "SBD"}selected="selected"{/if}>{__("currency_code_sbd")}</option>
	    <option value="SOS" {if $processor_params.currency == "SOS"}selected="selected"{/if}>{__("currency_code_sos")}</option>
	    <option value="ZAR" {if $processor_params.currency == "ZAR"}selected="selected"{/if}>{__("currency_code_zar")}</option>
	    <option value="KRW" {if $processor_params.currency == "KRW"}selected="selected"{/if}>{__("currency_code_krw")}</option>
	    <option value="ESP" {if $processor_params.currency == "ESP"}selected="selected"{/if}>{__("currency_code_esp")}</option>
	    <option value="LKR" {if $processor_params.currency == "LKR"}selected="selected"{/if}>{__("currency_code_lkr")}</option>
	    <option value="SHP" {if $processor_params.currency == "SHP"}selected="selected"{/if}>{__("currency_code_shp")}</option>
	    <option value="SDP" {if $processor_params.currency == "SDP"}selected="selected"{/if}>{__("currency_code_sdp")}</option>
	    <option value="SRD" {if $processor_params.currency == "SRD"}selected="selected"{/if}>{__("currency_code_srd")}</option>
	    <option value="SRG" {if $processor_params.currency == "SRG"}selected="selected"{/if}>{__("currency_code_srg")}</option>
	    <option value="SZL" {if $processor_params.currency == "SZL"}selected="selected"{/if}>{__("currency_code_szl")}</option>
	    <option value="SEK" {if $processor_params.currency == "SEK"}selected="selected"{/if}>{__("currency_code_sek")}</option>
	    <option value="CHF" {if $processor_params.currency == "CHF"}selected="selected"{/if}>{__("currency_code_chf")}</option>
	    <option value="SYP" {if $processor_params.currency == "SYP"}selected="selected"{/if}>{__("currency_code_syp")}</option>
	    <option value="TJS" {if $processor_params.currency == "TJS"}selected="selected"{/if}>{__("currency_code_tjs")}</option>
	    <option value="TZS" {if $processor_params.currency == "TZS"}selected="selected"{/if}>{__("currency_code_tzs")}</option>
	    <option value="THB" {if $processor_params.currency == "THB"}selected="selected"{/if}>{__("currency_code_thb")}</option>
	    <option value="TOP" {if $processor_params.currency == "TOP"}selected="selected"{/if}>{__("currency_code_top")}</option>
	    <option value="TTD" {if $processor_params.currency == "TTD"}selected="selected"{/if}>{__("currency_code_ttd")}</option>
	    <option value="TND" {if $processor_params.currency == "TND"}selected="selected"{/if}>{__("currency_code_tnd")}</option>
	    <option value="TRL" {if $processor_params.currency == "TRL"}selected="selected"{/if}>{__("currency_code_trl")}</option>
	    <option value="TMT" {if $processor_params.currency == "TMT"}selected="selected"{/if}>{__("currency_code_tmt")}</option>
	    <option value="AED" {if $processor_params.currency == "AED"}selected="selected"{/if}>{__("currency_code_aed")}</option>
	    <option value="UGX" {if $processor_params.currency == "UGX"}selected="selected"{/if}>{__("currency_code_ugx")}</option>
	    <option value="UAH" {if $processor_params.currency == "UAH"}selected="selected"{/if}>{__("currency_code_uah")}</option>
	    <option value="USD" {if $processor_params.currency == "USD"}selected="selected"{/if}>{__("currency_code_usd")}</option>
	    <option value="UZS" {if $processor_params.currency == "UZS"}selected="selected"{/if}>{__("currency_code_uzs")}</option>
	    <option value="VUV" {if $processor_params.currency == "VUV"}selected="selected"{/if}>{__("currency_code_vuv")}</option>
	    <option value="VEB" {if $processor_params.currency == "VEB"}selected="selected"{/if}>{__("currency_code_veb")}</option>
	    <option value="VEF" {if $processor_params.currency == "VEF"}selected="selected"{/if}>{__("currency_code_vef")}</option>
	    <option value="VND" {if $processor_params.currency == "VND"}selected="selected"{/if}>{__("currency_code_vnd")}</option>
	    <option value="YER" {if $processor_params.currency == "YER"}selected="selected"{/if}>{__("currency_code_yer")}</option>
	    <option value="CNY" {if $processor_params.currency == "CNY"}selected="selected"{/if}>{__("currency_code_cny")}</option>
	    <option value="YUM" {if $processor_params.currency == "YUM"}selected="selected"{/if}>{__("currency_code_yum")}</option>
	    <option value="ZMW" {if $processor_params.currency == "ZMW"}selected="selected"{/if}>{__("currency_code_zmw")}</option>
	    <option value="ZWD" {if $processor_params.currency == "ZWD"}selected="selected"{/if}>{__("currency_code_zwd")}</option>
	    <option value="PHP" {if $processor_params.currency == "PHP"}selected="selected"{/if}>{__("currency_code_php")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="type">{__("type")}:</label>
     <div class="controls">
         <select name="payment_data[processor_params][authmode]" id="type">
            <option value="A" {if $processor_params.authmode == "A"}selected="selected"{/if}>{__("fullauth")}</option>
            <option value="E" {if $processor_params.authmode == "E"}selected="selected"{/if}>{__("preauth")}</option>
             </select>
     </div>
</div>

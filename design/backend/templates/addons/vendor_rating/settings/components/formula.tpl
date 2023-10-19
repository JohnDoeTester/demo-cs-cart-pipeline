<div class="control-group setting-wide vendor-rating-formula" data-ca-vendor-rating-formula>
    <label for="elm_formula" class="control-label cm-required vendor-rating-formula__label">
        {__("vendor_rating.rating_formula")}
    </label>
    <div class="controls">
        <input type="text"
               id="elm_formula"
               class="input input-full vendor-rating-formula__input"
               name="addon_settings[formula]"
               value="{$addons.vendor_rating.formula}"
               data-ca-vendor-rating-formula-input
               data-ca-vendor-rating-label-class="vendor-rating-formula__label"
               data-ca-vendor-rating-formula-error-message="{__("vendor_rating.invalid_formula")}"
        />
        <span class="vendor-rating-formula__error help-inline"
              data-ca-vendor-rating-formula-error
        ></span>
    </div>
</div>

<div class="control-group setting-wide">
    <label for="" class="control-label">&nbsp;</label>
    <div class="controls">
        <p>{__("vendor_rating.variables")}</p>
        <dl>
            {foreach $criteria as $criterion}
                <dt class="vendor-rating-variable__variable">
                    <code>{$criterion.variable_name}</code>
                </dt>
                <dd class="vendor-rating-variable__name">
                    {__($criterion.name.template, $criterion.name.params)}
                </dd>
                <dd class="vendor-rating-variable__description">
                    {__($criterion.description.template, $criterion.description.params)}
                </dd>
            {/foreach}
        </dl>
    </div>
</div>


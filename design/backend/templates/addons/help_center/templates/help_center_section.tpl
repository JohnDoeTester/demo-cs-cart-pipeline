{* Slot: [data-ca-help-center="articles"] *}

<script type="text/template" data-ca-help-center="section" data-no-defer="true" data-no-execute="§"
    >{literal}<div class="help-center-section ${data.isShow ? '' : 'hidden'} ${data.class ? data.class : ''} help-center-section--${data.id}"
        id="content_help_center_${data.id}_${data.suffix}">
        <div class="help-center-section__header">
            <div class="help-center-section__header-title-wrapper">
                <h3 class="help-center-section__header-title">
                    ${data.name}
                </h3>
                ${data.dispatch_name
                    ? `<button type="button" class="help-center-section__header-reset" data-ca-help-center="resetDispatch">
                        <span class="cs-icon help-center-section__header-reset-text">${data.dispatch_name}</span>
                        <span class="cs-icon help-center-section__header-reset-icon icon-remove"></span>
                    </button>`
                    : ''
                }
            </div>
            ${data.action_url
                ? `<a href="${data.action_url}" target="_blank" class="help-center-btn help-center-btn--primary help-center-section__header-action">
                    ${data.action_name}
                </a>`
                : ''
            }
        </div>

        <div class="help-center-section__body">
            ${data.html ? `<div class="help-center-section__html">${data.html}</div>` : ''}

            <div class="help-center-section__articles help-center-section__articles--columns-${data.columns} ${data.columns > 1 ? 'help-center-section__articles--columns' : ''}"
                data-ca-help-center="articles"
                style="--help-center-section-columns: ${data.columns}"
                data-ca-help-center-no-data="${data.no_data_text}"
            ></div>

            ${data.all_items_url
                ? `<div class="help-center-section__bottom">
                    <a href="${data.all_items_url}" target="_blank" class="help-center-btn help-center-section__more">
                        ${data.all_items_name}
                    </a>
                </div>`
                : ''
            }
            <div class="help-center-section__footer">
                <div class="help-center-section__product-release-info">
                    ${data.product_release_info}
                </div>
            </div>
        </div>
</div>{/literal}</script>

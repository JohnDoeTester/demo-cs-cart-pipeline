{if "MULTIVENDOR"|fn_allowed_for && ($product_data.company_pre_moderation == "Y" || $product_data.company_pre_moderation_edit == "Y")}
    <div class="control-group">
        <label class="control-label">{__("approved")}:</label>
        <div class="controls">
        	<div class="text-type-value">
        		{if $product_data.approved == "Y"}{__("yes")}{elseif $product_data.approved == "P"}{__("pending")}{else}{__("no")}{/if}
        	</div>
        </div>
    </div>
{/if}
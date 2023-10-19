{assign var="id" value=$id|default:"supplier_id"}
{assign var="name" value=$name|default:"supplier_id"}

<div class="{$class|default:"control-group"}">
    <label class="control-label">{__("search_by_supplier")}</label>
    <div class="controls">
        {include file="addons/suppliers/views/suppliers/components/picker/picker.tpl" 
            input_name=$name
            item_ids=[$search.supplier_id]
        }
    </div>
</div>
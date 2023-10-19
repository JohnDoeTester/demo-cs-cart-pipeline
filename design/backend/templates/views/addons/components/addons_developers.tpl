<div class="sidebar-row">
    <h6>{__("developers")}</h6>
    <div class="sidebar-field btn-group">
        <button class="btn dropdown-toggle" data-toggle="dropdown">
            {__("select_developer")}
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" id="elm_all_dev_pages">
            {foreach $all_suppliers as $supplier_name => $supplier_data}
                <li><a href="{$supplier_data.href|fn_url}">{$supplier_name}</a></li>
            {/foreach}
        <!--elm_all_dev_pages--></ul>
    </div>
    <div class="sidebar-field">
        <p>{__("popular_developer")}:</p>
        <ul name="supplier_page" class="nav nav-list saved-search" id="elm_developer_pages">
            {foreach $suppliers as $supplier_name => $supplier_data}
                <li><a href="{$supplier_data.href|fn_url}">{$supplier_name}</a></li>
            {/foreach}
        <!--elm_developer_pages--></ul>
    </div>
</div>
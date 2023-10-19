<div id="conflicts_content_{$package_id}">
    {assign var="backups_dir" value=$config.dir.backups|fn_get_rel_dir}
    <p>{__("local_modifications_message", ["[dir]" => $backups_dir|trim:"/\\"]) nofilter}</p>
    {$result_ids = "conflicts_content_`$package_id`"}
    <div class="table-responsive-wrapper">
        <table class="table table-condensed table--relative table-responsive">
            <thead>
                <tr>
                    <th>{__("files")}</th>
                    <th class="left">{__("status")}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $package.conflicts as $file_id => $file_data}
                    <tr>
                        <td data-th="{__("files")}">
                            {$file_data.file_path}
                        </td>
                        <td width="10%" class="left" data-th="{__("status")}">
                            {if $file_data.status == "C"}
                                <div class="btn-group dropleft">
                                    <button class="btn btn-danger btn-small dropdown-toggle" data-toggle="dropdown">{__("not_checked")} <span class="caret"></span></button>
                                    <ul class="dropdown-menu">
                                        <li><a href="{"upgrade_center.resolve_conflict?package_id=$package_id&file_id=$file_id&status=R"|fn_url}" class="cm-ajax" data-ca-target-id="{$result_ids}">{__("checked")}</a></li>
                                    </ul>
                                </div>
                            {elseif $file_data.status == "R"}
                                <div class="btn-group dropleft">
                                    <button class="btn btn-success btn-small dropdown-toggle" data-toggle="dropdown">{__("checked")} <span class="caret"></span></button>
                                    <ul class="dropdown-menu">
                                        <li><a href="{"upgrade_center.resolve_conflict?package_id=$package_id&file_id=$file_id&status=C"|fn_url}" class="cm-ajax" data-ca-target-id="{$result_ids}">{__("not_checked")}</a></li>
                                    </ul>
                                </div>
                            {/if}
                            
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    <div class="buttons-container">
        <a class="cm-dialog-closer cm-cancel tool-link btn">{__("close")}</a>
    </div>

<!--conflicts_content_{$package_id}--></div>
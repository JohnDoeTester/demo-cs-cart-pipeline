(function(_, $)
{
    $.Redactor.prototype.imageupload = function()
    {
        return {
            getTemplate: function()
            {
                return String()
                + '<div>'
                    + '<section>'
                        + '<label>' + fn_strip_tags(_.tr('image_url')) + '</label>'
                        + '<input type="text" name="redactor_file_link" id="redactor_file_link" />'
                    + '</section>'
                    + '<section>'
                        + '<button id="elfinder_control">'+ fn_strip_tags(_.tr("browse")) + '</button>'
                    + '</section>'
                    + '<footer>'
                        + '<button id="redactor-modal-button-cancel">' + this.lang.get('cancel') + '</button>'
                        + '<button id="redactor-modal-button-action">' + this.lang.get('insert') + '</button>'
                    + '</footer>'
                + '</div>';
            },
            init: function()
            {
                var button = this.button.add('image', this.lang.get('image'));
                this.button.setIcon(button, '<i class="re-icon-image"></i>');
                this.button.addCallback(button, this.imageupload.load);
            },
            load: function()
            {
                this.modal.addTemplate('imageupload', this.imageupload.getTemplate());

                this.modal.addCallback('imageupload', function()
                {
                    $('#redactor_file_link').focus();
                    $('#elfinder_control').click(function(){
                        var options = $.extend(_.fileManagerOptions, {
                            url : fn_url('elf_connector.images?security_hash=' + _.security_hash),
                            getFileCallback: function(file) {
                                $('#elfinder_browser').dialog('close');
                                $('#redactor_file_link').val(file.url + '?' + new Date().getTime());
                            },
                        });
                        $('<div id="elfinder_browser"/>').elfinder(options).dialog({
                            width: 900,
                            modal: true,
                            title: fn_strip_tags(_.tr('file_browser')),
                            close: function (event, ui) {
                                $('#elfinder_browser').dialog('destroy').elfinder('destroy').remove();
                            }
                        }).closest('.ui-dialog').css('z-index', 50001);
                    });
                });

                this.modal.load('imageupload', fn_strip_tags(_.tr('insert_image')), 600);

                var button = this.modal.getActionButton();
                button.on('click', this.imageupload.insert);

                this.selection.save();
                this.modal.show();
            },
            insert: function(e)
            {
                var val = $('#redactor_file_link').val();

                if (val !== '')
                {
                    this.modal.close();

                    this.insert.html('<img src="' + val + '" />');
                }
                else {
                    this.modal.show();
                }
            }
        };
    };
}(Tygh, Tygh.$));
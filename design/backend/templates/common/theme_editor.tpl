{script src="js/lib/ace/ace.js"}
<div class="theme-editor-container" id="theme_editor">
<div class="theme-editor"></div>
<script>
(function(_, $) {
    $.extend(_, {
        query_string: encodeURIComponent('{$smarty.server.QUERY_STRING|escape:javascript nofilter}')
    });
})(Tygh, Tygh.$);
</script>
{script src="js/tygh/theme_editor.js"}
<!--theme_editor--></div>

/* editior-description:do_not_use */
(function (_, $) {
  $.ceEditor('handlers', {
    run: function run(elm) {
      elm.change(function () {
        elm.ceEditor('changed', elm.val());
      });
      return true;
    },
    destroy: function destroy(elm) {
      return true;
    },
    recover: function recover(elm) {
      return true;
    },
    updateTextFields: function updateTextFields(elm) {
      return true;
    },
    val: function val(elm, value) {
      if ($.type(value) == 'undefined') {
        return elm.val();
      }

      elm.val(value);
    },
    disable: function disable(elm, value) {
      return true;
    },
    insert: function insert(elm, value) {
      return true;
    }
  });
})(Tygh, Tygh.$);
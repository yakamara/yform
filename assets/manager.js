var be_relation_media_counter = 100; // no conflicts to 100 media in one page

$(document).on('rex:ready', function (event, container) {
  container.find('[data-be-media-wrapper]').each(function() {

    be_relation_media_counter++;

    $(this)
        .find("[id^='REX_MEDIA_'],[id^='REX_MEDIALIST_SELECT_']").each(function() {
      $(this).attr("id", $(this).attr("id") + be_relation_media_counter);
    });

    let regexpMedia = [
      new RegExp("(openREXMedia\\('?\\d+)", 'g'),
      new RegExp("(addREXMedia\\('?\\d+)", 'g'),
      new RegExp("(deleteREXMedia\\('?\\d+)", 'g'),
      new RegExp("(viewREXMedia\\('?\\d+)", 'g'),
      new RegExp("(moveREXMedialist\\('?\\d+)", 'g'),
      new RegExp("(openREXMedialist\\('?\\d+)", 'g'),
      new RegExp("(addREXMedialist\\('?\\d+)", 'g'),
      new RegExp("(deleteREXMedialist\\('?\\d+)", 'g'),
      new RegExp("(viewREXMedialist\\('?\\d+)", 'g'),
    ];

    $(this)
        .find("[onclick]").each(function() {
      var elementOnClick = $(this).attr("onclick");

      for (var i in regexpMedia) {
        elementOnClick = elementOnClick.replace(regexpMedia[i], '$1' + be_relation_media_counter);
      }
      $(this).attr("onclick", elementOnClick);

    });

    // replace be medialist
    $(this).find('select[id^="REX_MEDIALIST_"]').each(function() {
      var $input  = $(this).parent().children('input:first');
      var id = $(this).prop('id').replace('REX_MEDIALIST_SELECT_', '');
      $input.prop('id', 'REX_MEDIALIST_'+ id);
    });

  });
  container.find('form.yform-manager-multi-edit').each(function () {
    var $form = $(this);
    $form.find('[data-multi-edit-checkbox]').each(function () {
        var $checkbox = $(this);
        var $wrapper = $checkbox.closest('div');
        var id = $wrapper.attr('id');
        // extract "_multi_edit"
        id = id.substring(0, id.length - 11);
        var $target = $form.find('#' + id);

        var toggle = function () {
            if ($checkbox.prop('checked')) {
                $target.find(':input').prop('disabled', false);
                $target.find(':input[data-multi-edit-original-disabled]').prop('disabled', true).removeAttr('data-multi-edit-original-disabled');
                $target.find('a.btn').removeClass('disabled');
                $target.find('a.btn[data-multi-edit-original-disabled]').addClass('disabled').removeAttr('data-multi-edit-original-disabled');
            } else {
                $target.find(':input:disabled').attr('data-multi-edit-original-disabled', 1);
                $target.find(':input').prop('disabled', true);
                $target.find('a.btn[data-disabled], a.btn.disabled').attr('data-multi-edit-original-disabled', 1);
                $target.find('a.btn').addClass('disabled');
            }
        };

        toggle();
        $checkbox.change(toggle);
    });
});
  container.find('#rex-yform-history-modal').on('hidden.bs.modal', function () {
    $(this).removeData('bs.modal').find(".modal-content").empty();
});
});

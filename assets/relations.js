$(document).on('rex:ready', function (event, container) {
  container.find('[data-yform-be-relation-moveup]').each(function() {
    $(this).on('click', function() {
      var $thisElement = $(this).closest('[data-yform-be-relation-item]');
      var $prev = $thisElement.prev();
      if($prev) {
        $prev.before($thisElement);
      }
    });
  });
  container.find('[data-yform-be-relation-movedown]').each(function() {
    $(this).on('click', function() {
      var $thisElement = $(this).closest('[data-yform-be-relation-item]');
      var $next = $thisElement.next();
      if($next) {
        $next.after($thisElement);
      }
    });
  });
  container.find('[data-yform-be-relation-delete]').each(function() {
    $(this).on('click', function() {
      $('#'+$(this).attr('data-yform-be-relation-delete')).remove();
    })
  });
  container.find('[data-yform-be-relation-sortable]').each(function() {
    $(this).sortable({
      handle: ".sorthandle"
    });
  });
  container.find('[data-yform-be-relation-add]').each(function() {
    $(this).on('click', function() {

      var $wrapper = $(this).closest('[data-yform-be-relation-form]');
      var id = $wrapper.attr('id');
      var relationKey = $wrapper.attr('data-yform-be-relation-key');

      var prototypeForm = $wrapper.attr('data-yform-be-relation-form');
      var index = $wrapper.attr('data-yform-be-relation-index');

      ++index;
      $wrapper.attr('data-yform-be-relation-index',index);

      var rep = new RegExp(relationKey, "g");

      prototypeForm = prototypeForm.replace(rep, index);
      var $prototypeForm = $(prototypeForm);

      var dataItems = $(this).attr('data-yform-be-relation-add-position');

      var $dataItems;

      if (typeof dataItems !== "undefined") {
        $dataItems = $wrapper.find('[data-yform-be-relation-item='+dataItems+']'); // id matchen
        $dataItems.before($prototypeForm);

      } else {
        $dataItems = $wrapper.find('[data-yform-be-relation-item='+id+']'); // id matchen
        $dataItems.append($prototypeForm);

      }

      $prototypeForm.trigger('rex:ready', [$prototypeForm]);

    });

  });
});


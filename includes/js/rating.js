jQuery(document).ready(function($) {
  $.fn.rating = function (callback) {
    callback = callback || function () {
      };
    this.each(function (i, v) {

      $(v).data('rating', {callback: callback})
        .bind('init.rating', $.fn.rating.init)
        .bind('set.rating', $.fn.rating.set)
        .bind('hover.rating', $.fn.rating.hover)
        .trigger('init.rating');
    });
  };

  $.extend($.fn.rating, {
    init: function (e) {
      var el = $(this),
        list = '',
        isChecked = null,
        childs = el.children(),
        i = 0,
        l = childs.length;

      for (i = 0; i < l; i++) {
        list = list + '<a class="star" title="' + $(childs[i]).val() + '" />';
        if ($(childs[i]).is(':checked')) {
          isChecked = $(childs[i]).val();
        }
      }
      childs.hide();
      el
        .append('<div class="stars">' + list + '</div>')
        .trigger('set.rating', isChecked);
      $('a', el).bind('click', $.fn.rating.click);
      el.trigger('hover.rating');
    },
    set: function (e, val) {
      var el = $(this),
        item = $('a', el),
        input = undefined;

      if (val) {
        item.removeClass('fullStar');
        input = item.filter(function (i) {
          if ($(this).attr('title') == val)
            return $(this);
          else
            return false;
        });

        input
          .addClass('fullStar')
          .prevAll()
          .addClass('fullStar');
      }
      return;
    },
    hover: function (e) {
      var el = $(this),
        stars = $('a', el);

      stars.bind('mouseenter', function (e) {
        $(this)
          .addClass('tmp_feedback_service')
          .prevAll()
          .addClass('tmp_feedback_service');

        $(this).nextAll()
          .addClass('tmp_customer_feedback');
      });

      stars.bind('mouseleave', function (e) {
        $(this)
          .removeClass('tmp_feedback_service')
          .prevAll()
          .removeClass('tmp_feedback_service');

        $(this).nextAll()
          .removeClass('tmp_customer_feedback');
      });
    },
    click: function (e) {
      e.preventDefault();
      var el = $(e.target),
        container = el.parent().parent(),
        inputs = container.children('input'),
        rate = el.attr('title');

      matchInput = inputs.filter(function (i) {
        if ($(this).val() == rate)
          return true;
        else
          return false;
      });

      matchInput
        .prop('checked', true)
        .siblings('input').prop('checked', false);

      container
        .trigger('set.rating', matchInput.val())
        .data('rating').callback(rate, e);
    }
  });
});

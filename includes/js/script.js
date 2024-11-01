jQuery(document).ready(function($) {
  $('#choose_product').rating();
  $('#manager_cons').rating();
  $('#product_delivery').rating();
  $('#payment_process').rating();

  $('#comment-choose_product').click(function(){
    $('.comment_choose_product').css('display','block');
    $(this).hide();
  });

  $('#comment-product_delivery').click(function(){
    $('.comment_product_delivery').css('display','block');
    $(this).hide();
  });

  $('#comment-payment_process').click(function(){
    $('.comment_payment_process').css('display','block');
    $(this).hide();
  });

  $('#comment-manager_cons').click(function(){
    $('.comment_manager_cons').css('display','block');
    $(this).hide();
  });
  $(".aplk_close").click(function(){
    $(".aplk_overlay").removeClass('visible').addClass('hidden');
  });

});

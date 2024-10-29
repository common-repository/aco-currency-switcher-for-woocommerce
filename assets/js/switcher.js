// Load more currencys
var lomoreHandler = (e) => {
  if(!jQuery('.moreWindow').hasClass('open')){
    jQuery('.moreWindow').addClass('open').animate({top: '0'}, 200);
  }else{
    jQuery('.moreWindow').removeClass('open').animate({top: '100%'}, 200);
  }
}


// Close more curriences
var hideSwitcherShowcase = (e) => {
  if(jQuery('.moreWindow').hasClass('open')){
    jQuery('.moreWindow').removeClass('open').animate({top: '100%'}, 200);
  }
}


// Change Currences 
var acowcs_change_curriences = (cr) => {
  cr = typeof cr.value != 'undefined' ? cr.value : cr;
  let data = {
    currency: cr,
    user_id: window.acowcs_object.user_id
  }
  
  localStorage.setItem("is_currency_change", true);
  fetch(window.acowcs_object.root + "change_shop_currency/", {
    credentials: 'same-origin',
    method: "POST", 
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-WP-Nonce': window.acowcs_object.api_nonce,
    },
    body: JSON.stringify(data)
  }).then(res => {
    res.json().then(json => {
      if(json.msg == 'success'){
        jQuery(document.body).trigger('wc_fragment_refresh');
        location.reload();
      }
    });
  });
}

jQuery(document).ready(function($){
  setTimeout(function () {
    let is_currency_change = localStorage.getItem('is_currency_change');
    if(is_currency_change){
      jQuery(document.body).trigger('wc_fragment_refresh');
      localStorage.removeItem("is_currency_change");
    }
  }, 500);


  let widthHide = jQuery('#acowcs_switcher').width() - 40;
  jQuery('#acowcs_switcher').css('opacity', 1);
  //Default position

  if(jQuery('div#acowcs_switcher').hasClass('left')){
    jQuery('div#acowcs_switcher').css('left', '-'+widthHide+ 'px');
  }
  if(jQuery('div#acowcs_switcher').hasClass('right')){
    jQuery('div#acowcs_switcher').css('right', '-'+widthHide+ 'px');
  }
  // Left
  jQuery(document).on('mouseenter', 'div#acowcs_switcher.left', function(){
    let left = '0px';
    jQuery(this).animate({
        left: left
    }, 100);
  }).on('mouseleave', 'div#acowcs_switcher.left', function(){
     
     jQuery(this).animate({
       left: '-' + widthHide + 'px'
     })
  });


  // Right
  jQuery(document).on('mouseenter', 'div#acowcs_switcher.right', function(){
    let right = '0px';
    jQuery(this).animate({
      right: right
    }, 100);
  }).on('mouseleave', 'div#acowcs_switcher.right', function(){
      let right = '-'+widthHide+'px';
     jQuery(this).animate({
       right: right
     })
  });

  

});
let data = {};
let handleUpdate = (e) => {
    document.getElementById('colculateProcessBtn').setAttribute("disabled", true);
    data[e.name] = e.value;
    if(typeof data.converted_amount == 'undefined' || data.converted_amount == '')
        return false;

    if(typeof data.currency_from == 'undefined' || data.currency_from == '')
        return false;

    if(typeof data.currency_to == 'undefined' || data.currency_to == '')
        return false;
    
    document.getElementById('colculateProcessBtn').removeAttribute("disabled");
}


let calculateCurrency = (e) => {
    jQuery('div#acowcs_calculator .loader').css('width', '100%');
    jQuery(e).addClass('active');
    let inputdata = {
        converted_amount: data.converted_amount, 
        from: data.currency_from,
        to: data.currency_to
    }

    fetch(window.acowcs_object.root + "calculate_currency/", {
        credentials: 'same-origin',
        method: "POST", 
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.acowcs_object.api_nonce,
        },
        body: JSON.stringify(inputdata)
      }).then(res => {
        res.json().then(json => {
            document.getElementById('calculator_result').innerHTML = json.result;
            if(json.msg == 'success' && json.error != '' && json.result == 'error'){
                jQuery('#currencyError').html('<span>'+json.error+'</span>').show();    
            }

            jQuery('div#acowcs_calculator .loader').css('width', '0');
            jQuery('div#calculator_result').css('opacity', 1);
            jQuery(e).removeClass('active');
        });
      });
}

function formatState (opt) {
    if (!opt.id) {
        var $state = jQuery('<span class="empty_space">'+opt.text+'</span>');
        return $state;
    } 

    var optimage = jQuery(opt.element).attr('data-image'); 
    
    if(!optimage){
       return opt.text;
    } else {                    
        var $opt = jQuery(
           '<span class="bg_flag" style="background-image:url('+optimage+') "></span><span>'+ opt.text +'</span>'
        );
        return $opt;
    }
}

let select2Object = {
    templateResult: formatState,
    templateSelection: formatState
}

if(typeof jQuery.fn.selectWoo != 'undefined'){
    jQuery.fn.selectWoo.amd.define('customSingleSelectionAdapter', [
        'select2/utils',
        'select2/selection/single',
    ], function (Utils, SingleSelection) {
        const adapter = SingleSelection;
        adapter.prototype.update = function (data) {
        if (data.length === 0) {
            this.clear();
            return;
        }
        var selection = data[0];
        var $rendered = this.$selection.find('.select2-selection__rendered');
        var formatted = this.display(selection, $rendered);
        $rendered.empty().append(formatted);
        $rendered.prop('title', selection.title || selection.text);
        };
        return adapter;
    });
    select2Object.selectionAdapter = jQuery.fn.selectWoo.amd.require('customSingleSelectionAdapter');
}

if(jQuery('.currency_select2').length){
    jQuery(".currency_select2").select2(select2Object);
}



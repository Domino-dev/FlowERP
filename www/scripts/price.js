document.addEventListener('DOMContentLoaded', () => {
    
    if(!$('#price-product-internal-id').val()){
	$('#snippet--productPrices').hide();
    }
    
    
    let debounceTimeoutPirceSearch;
    $(document).on('keyup','#prices-search',function(){
	let searchSlug = $(this).val();
	clearTimeout(debounceTimeoutPirceSearch); 
	debounceTimeoutPirceSearch = setTimeout(() => {
	    if(searchSlug.length > 3 || searchSlug.length < 1){
		naja.makeRequest('POST','?do=getPricesSearch',{searchSlug:searchSlug},{history:false})
		.then((resp) => {

		})
		.catch((err) => {
		   console.log(err); 
		});
	    }
	},500);
    });
    
    $(document).on('click','.price-page-button',function(){
	let pageNumber = $(this).data('page-number');
	let searchSlug = $('#prices-search').val();

	naja.makeRequest('POST','?do=redrawPageData',{pageNumber:pageNumber,searchSlug:searchSlug},{history:false})
	.then((resp) => {

	})
	.catch((err) => {
	   console.log(err); 
	});
    });
    
    $(document).on('keyup','#price-product-catalogue-code-search', function(){
	let slug = $(this).val();
	
	if(slug.length > 2){
	    naja.makeRequest('POST','?do=getProductAutocomplete',{slug: slug},{history:false})
	    .then((resp) => {
		if(resp){
		    $('#product-autocomplete').html(resp);
		} else {
		    $('#product-autocomplete').html(null);
		}
	    })
	    .catch((err) => {
		alert(err);
	    })
	} else {
	    $('#product-autocomplete').html(null);
	}
    });
    
    $(document).on('click','.product-autocomplete-row', function(){
	$('#snippet--productPrices').show();
	
	let productInternalID = $(this).data('product-internal-id');
	
	if(productInternalID){
	    naja.makeRequest('POST','?do=getProductPricesData',{productInternalID: productInternalID},{history:false})
	    .then((resp) => {
		$('#price-product-internal-id').val(productInternalID);
	    })
	    .catch((err) => {
		alert(err);
	    })
	}
    });
    
    $(document).on('change','#price-price-list',function(){
	let priceListInternalID = $(this).val();
	
	if(priceListInternalID){
	    naja.makeRequest('POST','?do=getPriceListData',{priceListInternalID: priceListInternalID},{history:false})
	    .then((resp) => {
		if(resp){
		    $('#price-list-internal-id').html(resp.internalID);
		    $('#currency-ISO-code').html(resp.currency);
		    $('#is-with-VAT').html(resp.isWithVAT ? ' with VAT' : ' without VAT');
		}
	    })
	    .catch((err) => {
		alert(err);
	    })
	}
    });
    
    
});


document.addEventListener('DOMContentLoaded', () => {
    let debounceTimeoutPriceListSearch;
    $(document).on('keyup','#price-lists-search',function(){
	let searchSlug = $(this).val();
	clearTimeout(debounceTimeoutPriceListSearch); 
	debounceTimeoutPriceListSearch = setTimeout(() => {
	    if(searchSlug.length > 3 || searchSlug.length < 1){
		naja.makeRequest('POST','?do=getPriceListsSearch',{searchSlug:searchSlug},{history:false})
		.then((resp) => {

		})
		.catch((err) => {
		   console.log(err); 
		});
	    }
	    
	},500);
    });
    
    $(document).on('click','.price-list-page-button',function(){
	let pageNumber = $(this).data('page-number');
	let searchSlug = $('#price-lists-search').val();

	naja.makeRequest('POST','?do=redrawPageData',{pageNumber:pageNumber,searchSlug:searchSlug},{history:false})
	.then((resp) => {

	})
	.catch((err) => {
	   console.log(err); 
	});
    });
});


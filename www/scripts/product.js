document.addEventListener('DOMContentLoaded', () => {
    let debounceTimeoutProductSearch;
    $(document).on('keyup','#products-search',function(){
	let searchSlug = $(this).val();
	clearTimeout(debounceTimeoutProductSearch); 
	debounceTimeoutProductSearch = setTimeout(() => {
	    if(searchSlug.length > 3 || searchSlug.length < 1){
		naja.makeRequest('POST','?do=getProductsSearch',{searchSlug:searchSlug},{history:false})
		.then((resp) => {

		})
		.catch((err) => {
		   console.log(err); 
		});
	    }
	    
	},500);
    });
    
    $(document).on('click','.product-page-button',function(){
	let pageNumber = $(this).data('page-number');
	let searchSlug = $('#products-search').val();

	naja.makeRequest('POST','?do=redrawPageData',{pageNumber:pageNumber,searchSlug:searchSlug},{history:false})
	.then((resp) => {

	})
	.catch((err) => {
	   console.log(err); 
	});
    });
    
    
});


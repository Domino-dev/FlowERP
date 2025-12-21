document.addEventListener('DOMContentLoaded', () => {
    
    let debounceTimeout;
    $(document).on('keyup','#masterAutocomplete',function(){
	const self = this; 
	clearTimeout(debounceTimeout); 
	debounceTimeout = setTimeout(() => {
        let searchedVal = $(self).val();
        if (searchedVal.length > 3) {
            $('#masterIdHolder').val('');
            $('#masters-autocomplete').show();
            
	    naja.makeRequest('POST','?do=autocompleteMasterCustomer',{searchedVal:searchedVal},{ history: false })
	    .then((resp) => {
		if(resp){
		    $("#masters-autocomplete").html(resp);
		}
	    })
	    .catch((err) => {
		console.log(err);
	    });
        }
    }, 500);
    });


    $(document).on('click','#master-autocomplete-record',function(){
	let masterInternalID = $(this).data('mid');
	naja.makeRequest('POST','?do=fillMasterCustomer',{masterInternalID:masterInternalID},{history:false})
	.then((resp) => {
	    if(resp){
		$('#masterIdHolder').val(masterInternalID);
		$('#companyName').val(resp.companyName);
		$('#customer-vat').val(resp.companyNumber);
		$('#addressStreet').val(resp.street);
		$('#addressCity').val(resp.city);
		$('#addressZip').val(resp.zip);
	    }
	})
	.catch((err) => {
	   console.log(err); 
	});
    });
    
    $(document).on('focusout','#customer-identificator',function(){
	
	let identificator = $(this).val();
	let customerInternalID = $('#customer-internal-id');
	if(identificator.length > 3 && customerInternalID.length === 0){
	    naja.makeRequest('POST','?do=checkIdentificatorUniqueness',{identificator:identificator},{history:false})
	    .then((resp) => {
		if(resp){
		    $("#customer-identificator-unique-alert").html('This identificator '+identificator+' is already in use!');
		} else {
		    $("#customer-identificator-unique-alert").html(null);
		}
	    })
	    .catch((err) => {
	       console.log(err); 
	    });
	}
    });
    
    let debounceTimeoutCustomerSearch;
    $(document).on('keyup','#customer-search',function(){
	let searchSlug = $(this).val();
	clearTimeout(debounceTimeoutCustomerSearch); 
	debounceTimeoutCustomerSearch = setTimeout(() => {
	    if(searchSlug.length > 3 || searchSlug.length < 1){
		naja.makeRequest('POST','?do=customersSearch',{searchSlug:searchSlug},{history:false})
		.then((resp) => {

		})
		.catch((err) => {
		   console.log(err); 
		});
	    }
	    
	},500);
    });
    
    $(document).on('click','.customer-page-button',function(){
	let pageNumber = $(this).data('page-number');
	let searchSlug = $('#customer-search').val();

	naja.makeRequest('POST','?do=redrawPageData',{pageNumber:pageNumber,searchSlug:searchSlug},{history:false})
	.then((resp) => {

	})
	.catch((err) => {
	   console.log(err); 
	});
    });
});


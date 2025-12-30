document.addEventListener('DOMContentLoaded', () => {
    
    $(document).on('change','#invoice-price-list-internal-id',function (){
	let priceListInternalID = $(this).val();
	
	/*if(priceListInternalID){
	    naja.makeRequest('POST','?do=getPriceListData',{priceListInternalID:priceListInternalID},{history:false})
	    .then((resp) => {
		console.log(resp);
		if(resp && resp !== null){
		    let currencyAndVatStatus = resp['currency'] ?? "";
		    currencyAndVatStatus += resp['isWithVAT'] ? ' with VAT' : ' without VAT';
		    $('#currency-ISO-code').html(currencyAndVatStatus);
		    $('#customer-autocomplete-suggestions').html(resp);
		    $('#is-price-list-with-vat').val(resp['isWithVAT']);
		    $('#item-vat-percentage').val(resp['vatPercentage']);
		}
	    })
	    .catch((err) => {
		alert(err);
	    })
	}*/
	
	const productData = collectProductInternalIDs();
	setPricesAsZero();
	const productInternalIDs = productData[0];
	const productCatalogueCodes = productData[1];
	
	if(productInternalIDs || productCatalogueCodes){
	    naja.makeRequest('POST','?do=getPrices',{
		priceListInternalID: priceListInternalID, 
		productInternalIDsJSON: JSON.stringify(productInternalIDs),
		productCatalogueCodesJSON: JSON.stringify(productCatalogueCodes)},
	    {history:false})
	    .then((resp) => {
		console.log(resp);
		if(resp && Object.values(resp).length > 0){
		    updatePrices(resp);
		}
	    })
	    .catch((err) => {
		alert(err);
	    })
	}
	
    });
    
    let debounceCustomerAutocomplete;
    $(document).on('keyup','#frm-invoiceForm-customerAutocomplete',function(){
	clearTimeout(debounceCustomerAutocomplete);
	debounceCustomerAutocomplete = setTimeout(() => {
	    let slug = $(this).val();
	    if(slug && slug.length > 3){
		naja.makeRequest('POST','?do=getCustomerAutocompleteSuggestion',{slug:slug},{history:false})
		.then((resp) => {
		    if(resp && resp !== null){
			$('#customer-autocomplete-suggestions').html(resp);
		    }
		})
		.catch((err) => {
		    alert(err);
		})
	    } else {
		$('#customer-autocomplete-suggestions').html(null);
	    }
	}, 500);
    });
    
    $(document).on('click','.multiplier-add-button,.multiplier-remove-button',function(){
	naja.snippetHandler.addEventListener('afterUpdate', (event) => {
	    if (event.detail.snippet.id === 'snippet--dynamicInvoiceForm') {
		calculateInvoiceTotalPrice();
	    }
	});
    });
    
    $(document).on('click','.customer-autocomplete-suggestion',function(){
	$('#frm-invoiceForm-customerAutocomplete').val(null);
	let customerInternalID = $(this).data('customer-internal-id');
	if(customerInternalID){
	    naja.makeRequest('POST','?do=getCustomerData',{customerInternalID:customerInternalID},{history:false})
	    .then((resp) => {
		if(resp && resp !== null){
		    $('#customer-internal-id').val(resp['internalID']);
		    $('#customer-identificator').val(resp['identificator']);
		    $('#customer-name').val(resp['name']);
		    $('#customer-email').val(resp['email']);
		    $('#customer-phone').val(resp['phone']);
		    $('#customer-company-name').val(resp['companyName']);
		    $('#customer-company-number').val(resp['companyNumber']);
		    $('#customer-vat-number').val(resp['vatNumber']);
		    
		    $('#billing-address-street').val(resp['billingAddress']['street']);
		    $('#billing-address-city').val(resp['billingAddress']['city']);
		    $('#billing-address-zip').val(resp['billingAddress']['zip']);
		    $('#billing-address-country').val(resp['billingAddress']['countryISO']);
		    
		    if(resp['deliveryAddress']){
			$('#delivery-address-street').val(resp['deliveryAddress']['street']);
			$('#delivery-address-city').val(resp['deliveryAddress']['city']);
			$('#delivery-address-zip').val(resp['deliveryAddress']['zip']);
			$('#delivery-address-country').val(resp['deliveryAddress']['countryISO']);
		    }
		    
		    $('#invoice-price-list-internal-id').val(resp['priceListInternalID']);
		    
		    // due date
		    let date = new Date();
		    if (resp['dueDays']) {
			date.setDate(date.getDate() + parseInt(resp['dueDays']));  
		    }
		    const pad = n => n.toString().padStart(2, '0');
		    const formatted = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
		    $('#frm-invoiceForm-dueDate').val(formatted);
		}
	    })
	    .catch((err) => {
		alert(err);
	    })
	}
	
	$('#customer-autocomplete-suggestions').html(null);
    });
    
    let debounceProductAutocomplete;
    $(document).on('keyup','.item-catalogue-code',function(){
	clearTimeout(debounceProductAutocomplete);
	debounceProductAutocomplete = setTimeout(() => {
	    let slug = $(this).val();
	    
	    const input = $(this);
	    const multiplier = input.closest('.multiplier');
	    const suggestions = multiplier.find('.product-autocomplete-suggestions');
	    
	    naja.makeRequest('POST','?do=getProductAutocompleteSuggestion',{slug:slug},{history:false})
	    .then((resp) => {
		if(resp && resp !== null){
		    suggestions.html(resp);
		}
	    })
	    .catch((err) => {
		alert(err);
	    })
	},500);
    });
    
    $(document).on('click','.product-autocomplete-row',function(){
	let productInternalIDSuggestion = $(this).data('product-internal-id');
	let priceListInternalID = $('#invoice-price-list-internal-id').val();
	
	const input = $(this);
	const multiplier = input.closest('.multiplier'); // parent
	const productInternalIDEl = multiplier.find('.item-internal-id');
	const productCatalogueCodeEl = multiplier.find('.item-catalogue-code');
	const productNameEl = multiplier.find('.item-name');
	const productQuantityEl = multiplier.find('.item-quantity');
	const productPriceWithoutVATEl = multiplier.find('.item-price-without-VAT');
	const productPriceVATEl = multiplier.find('.item-vat-percentage');
	const productPriceWithoutVATTotalEl = multiplier.find('.total-item-price-without-VAT');
	const productPriceWithVATTotalEl = multiplier.find('.total-item-price-with-VAT');
	const suggestionsEl = multiplier.find('.item-autocomplete-suggestions');
	const autocompleteEl = multiplier.find('.item-autocomplete');
	
	if(productInternalIDSuggestion){
	    suggestionsEl.html(null);
	    autocompleteEl.val('');
	    
	    naja.makeRequest('POST','?do=getProductData',{productInternalID:productInternalIDSuggestion,priceListInternalID:priceListInternalID},{history:false})
	    .then((resp) => {
		if(resp && resp !== null){
		    let productPriceWithVATTotal;
		    let productPriceWithoutVATTotal;
		    
		    let productVatValue = resp['vatRate'];
		    let productQuantity = productQuantityEl.val();
		    let productPriceWithoutVAT = resp['priceValue'];
		    
		    let totalPrices = calculateItemTotalPrice(productPriceWithoutVAT,productVatValue, 0,productQuantity);
		    productPriceWithVATTotal = totalPrices['withVAT'];
		    productPriceWithoutVATTotal = totalPrices['withoutVAT'];
		    
		    productInternalIDEl.val(resp['internalID']);
		    productCatalogueCodeEl.val(resp['catalogueCode']);
		    productNameEl.val(resp['name']);
		    productPriceWithoutVATEl.val(productPriceWithoutVAT);
		    productPriceVATEl.val(resp['vatRate']);
		    
		    productPriceWithoutVATTotalEl.val(productPriceWithoutVATTotal);
		    productPriceWithVATTotalEl.val(productPriceWithVATTotal);
		    
		    calculateInvoiceTotalPrice();
		}
	    })
	    .catch((err) => {
		alert(err);
	    })
	}
	
	$('#customer-autocomplete-suggestions').html(null);
    });
    
    let debounceQuantityChange;
    $(document).on('change','.item-price-without-VAT,.item-quantity,.item-vat-percantage,.item-discount',function() {
	const input = $(this);
	const multiplier = input.closest('.multiplier'); // parent
	clearTimeout(debounceQuantityChange);
	debounceQuantityChange = setTimeout(() => {
	    recalculateItemTotalPrice(null,multiplier);
	},500);
    });
    
    function recalculateItemTotalPrice(input, multiplier){
	let productPriceWithVATTotal;
	let productPriceWithoutVATTotal;

	if(multiplier === null || multiplier === undefined){
	    console.log(input);
	    const multiplier = input.closest('.multiplier');
	}

	if(multiplier === undefined){
	    return ;
	}
	
	const productPriceWithoutVATEl = multiplier.find('.item-price-without-VAT');
	const productVatPercentageEl = multiplier.find('.item-vat-percentage');
	const productDiscountEl = multiplier.find('.item-discount');
	const productQunatityEl = multiplier.find('.item-quantity');

	const productPriceWithoutVATTotalEl = multiplier.find('.total-item-price-without-VAT');
	const productPriceWithVATTotalEl = multiplier.find('.total-item-price-with-VAT');

	const productPriceWithoutVATVal = productPriceWithoutVATEl.val();
	const productQunatityVal = productQunatityEl.val();
	const productVATValue = productVatPercentageEl.val();
	const productDiscountPercVal = productDiscountEl.val();

	const totalPrices = calculateItemTotalPrice(productPriceWithoutVATVal, productVATValue,productDiscountPercVal, productQunatityVal);
	productPriceWithVATTotal = totalPrices['withVAT'];
	productPriceWithoutVATTotal = totalPrices['withoutVAT'];

	productPriceWithoutVATTotalEl.val(productPriceWithoutVATTotal);
	productPriceWithVATTotalEl.val(productPriceWithVATTotal);

	calculateInvoiceTotalPrice();
    }
    
    function recalculateItemsPrices(){
	$('.multiplier').each(function() {
	    const multiplier = $(this);
	    const itemInternalIDEl = multiplier.find('.item-internal-id');
	    recalculateItemTotalPrice(null,multiplier);
	});
    }
    
    function collectProductInternalIDs(){
	let productInternalIDs = [];
	let productCatalogueCodes = [];
	
	$('.multiplier').each(function() {
	    const multiplier = $(this);
	    const productInternalID = multiplier.find('.item-internal-id').val();
	    const productCatalogueCode = multiplier.find('.item-catalogue-code').val();
	    
	    if(productInternalID){
		productInternalIDs.push(productInternalID);
	    } else {
		productCatalogueCodes.push(productCatalogueCode);
	    }
	});
	
	return [productInternalIDs, productCatalogueCodes];
    }
    
    function setPricesAsZero(){
	$('.multiplier').each(function() {
	    const multiplier = $(this);
	    multiplier.find('.item-price-without-VAT').val(0);
	    recalculateItemTotalPrice(null, multiplier);
	});
    }
    
    // Updates prices after a price list change
    function updatePrices(priceByProductInternalID){
	$('.multiplier').each(function() {
	    const multiplier = $(this);
	    const productInternalID = multiplier.find('.item-internal-id').val();
	    const productCatalogueCode = multiplier.find('.item-catalogue-code').val();
	    const itemPriceWihtouVATEl = multiplier.find('.item-price-without-VAT');

	    if(productInternalID && productInternalID.length > 0 && itemPriceWihtouVATEl){
		itemPriceWihtouVATEl.val(priceByProductInternalID[productInternalID] ?? 0);
	    }
	});
	
	recalculateItemsPrices();
    }
    
    function calculateItemTotalPrice(productPriceWithoutVAT, priceListVAT, productDiscountPercVal, productQuantity){
	let productPriceValueWithoutVATTotal = productPriceWithoutVAT * productQuantity;
	
	if(productDiscountPercVal && productDiscountPercVal > 0){
	    productPriceValueWithoutVATTotal = productPriceValueWithoutVATTotal * (1 - productDiscountPercVal/ 100)
	}
	
	let productPriceValueWithVATTotal;
	if(priceListVAT !== 0 && priceListVAT !== null){
	    productPriceValueWithVATTotal = productPriceValueWithoutVATTotal * (1+priceListVAT/100);
	} else {
	    productPriceValueWithVATTotal = productPriceValueWithoutVATTotal;
	}
	
	console.log(productPriceValueWithoutVATTotal);
	console.log(productPriceValueWithVATTotal);
	
	return {'withoutVAT': Math.round(productPriceValueWithoutVATTotal * 100) / 100,'withVAT': Math.round(productPriceValueWithVATTotal * 100) / 100};
    }
    
    function calculateInvoiceTotalPrice(){
	const totalWithoutVat = [...document.querySelectorAll('.multiplier')]
	.reduce((sum, multiplier) => {
	    const priceEl = multiplier.querySelector(
		'.total-item-price-without-VAT'
	    );

	    console.log(Number(priceEl.value));

	    return sum + Number(priceEl.value);
	}, 0);
	
	const totalWithVat = [...document.querySelectorAll('.multiplier')]
	.reduce((sum, multiplier) => {
	    const priceEl = multiplier.querySelector(
		'.total-item-price-with-VAT'
	    );

	    return sum + Number(priceEl.value);
	}, 0);
	console.log('wtf');
	$('#invoice-total-without-vat').html(totalWithoutVat);
	$('#invoice-total-with-vat').html(totalWithVat);
    }
    
    function getStatusCodesFromURL(){
	const url = new URL(window.location.href);
	const statusParam = url.searchParams.get('status');

	return statusParam ? statusParam.split(',') : [];
    }
    
    let debounceTimeoutCustomerSearch;
    $(document).on('keyup','#invoices-search',function(){
	clearTimeout(debounceTimeoutCustomerSearch); 
	debounceTimeoutCustomerSearch = setTimeout(() => {
	    let searchSlug = $(this).val();
	    if(searchSlug.length > 3 || searchSlug.length < 1){
		let invoiceStatusCode = getStatusCodesFromURL();
		let pageNumber = 1;
		
		naja.makeRequest('POST','?do=filterInvoices',{pageNumber:pageNumber,searchSlug:searchSlug, statusCode: invoiceStatusCode},{history:false})
		.then((resp) => {

		})
		.catch((err) => {
		   console.log(err); 
		});
	    }
	    
	},500);
    });
    
    $(document).on('click','.invoice-page-button',function(){
	let pageNumber = $(this).data('page-number');
	let searchSlug = $('#customer-search').val();
	let invoiceStatusCode = getStatusCodesFromURL();

	naja.makeRequest('POST','?do=filterInvoices',{pageNumber:pageNumber,searchSlug:searchSlug, statusCode: invoiceStatusCode},{history:false})
	.then((resp) => {

	})
	.catch((err) => {
	   console.log(err); 
	});
    });
    
    $(document).on('click','.invoice-status',function(){
	
	const url = new URL(window.location.href);
	const key = 'status';

	const values = [];

	$('.invoice-status:checked').each(function () {
	    values.push($(this).val());
	});

	console.log('Status values:', values);

	let pageNumber = 1;
	let searchSlug = $('#customer-search').val();

	naja.makeRequest('POST','?do=filterInvoices',
	    {
		pageNumber: pageNumber,
		searchSlug: searchSlug,
		statusCode: values
	    },
	    { history: false }
	)
	.then((resp) => {
	    if (values.length) {
		url.searchParams.set(key, values.join(','));
	    } else {
		url.searchParams.delete(key);
	    }

	    window.history.replaceState({}, '', url.toString());
	})
	.catch((err) => {
	    console.log(err);
	});
    });
});
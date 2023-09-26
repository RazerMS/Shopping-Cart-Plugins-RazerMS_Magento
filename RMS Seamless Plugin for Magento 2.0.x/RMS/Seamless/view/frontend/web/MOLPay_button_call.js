require(['jquery'], function($){
	
		$('body').on('click', 'input[name=payment_options]', function(){
			var myForm = $("#seamless");
			if (myForm[0].checkValidity()) {
				myForm.trigger("submit");
			}
			else
			{
				alert("Please fill in required field.");
				$(":input[required]").each(function () {
					if($(this).val().length == 0)
					{
						$(this).focus();
						return false;
					}
				});
			}
		});


});

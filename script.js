var $j = jQuery.noConflict();
$j(document).ready(function($) {
	$('#next_cat').click(function(){
		$("#result").animate({ 
		        opacity: 0,
		      }, 600 );			
			$.ajax({
				type:'GET',
				url:$('#path').val() + 'ajax_page.php',
				data:'start=' + $('#next_value').val(),
				complete:function(data){
				$("#result").animate({ 
				        opacity: 100,
				      }, 1500 );					
					$('#result').html(data.responseText);
				}
			});
			return false;			
		});	
	
	$('#prev_cat').click(function(){
		$("#result").animate({ 
		        opacity: 0,
		      }, 600 );			
			$.ajax({
				type:'GET',
				url:$('#path').val() + 'ajax_page.php',
				data:'start=' + $('#prev_value').val(),
				complete:function(data){
				$("#result").animate({ 
				        opacity: 100,
				      }, 1500 );					
					$('#result').html(data.responseText);
				}
			});
			return false;			
		});	
});
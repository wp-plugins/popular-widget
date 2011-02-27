jQuery(document).ready(function($){ 
	
	$("div.pop-inside ul").hide();
	$("div.pop-inside ul").eq(0).show();
	$("#pop-widget-tabs a").eq(0).addClass('active');
	
	$("#pop-widget-tabs a").click(function(){
		$("#pop-widget-tabs a").removeClass('active');								   
		$(this).addClass('active');
		inx = $("#pop-widget-tabs a").index($(this));
		$("div.pop-inside ul").hide();
		$("div.pop-inside ul").eq(inx).show();
		return false;
	});
});
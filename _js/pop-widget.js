jQuery(document).ready(function($){ 
	
	$.noConflict();
	
	$("div.pop-inside ul").hide();
	
	$(".pop-widget-tabs").each(function(){
		tabid = $(this).attr('id').replace('pop-widget-tabs-','');
		$("#pop-widget-tabs-"+tabid+" a").eq(0).addClass('active');	
		$(".pop-inside-"+tabid+" ul").eq(0).show();
	})
	
	$(".pop-widget-tabs a").click(function(){
		tab = $(this).attr('href').replace('#','');
		id  = $(this).parents('.pop-widget-tabs').attr('id').replace('pop-widget-tabs-','');
		
		$("#pop-widget-tabs-"+id+" a").removeClass('active');		
		$(this).addClass('active');
		
		inx = $("#pop-widget-tabs-"+id+" a").index($(this));
		$(".pop-inside-"+id+" ul").hide();
		$(".pop-inside-"+id+" ul").eq(inx).show();

		return false;
	});
	
});
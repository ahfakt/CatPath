jQuery(document).ready(function($){
	function cpGetTop(Top){
		return Top[0] ? '<li class="cp_parent" title="'+Top[1]+'"><a id="'+Top[2]+'"><span>'+Top[1]+'</span></a></li>' : '<li class="cp_root" title="'+Top[1]+'"><span>'+Top[1]+'</span></li>';
	}
	function cpGetIn(In){
		var out = '';
		$.each(In, function(i, val){
				if(val[0])
					out += '<li class="cp_post" title="'+val[1]+'"><a href="'+val[2]+'"><span>'+val[1]+'</span></a></li>';
				else
					out += '<li class="cp_sub" title="'+val[1]+'"><a id="'+val[2]+'"><span>'+val[1]+'</span></a></li>';
			});
		return out;
	}
	$("ul.cp_top").html(cpGetTop(cp_cache[0]));
	$("ul.cp_in").html(cpGetIn(cp_cache[1]));
	$(".widget.CatPath").on("click", "li.cp_sub a" , function() {
		$.getJSON('/wp-admin/admin-ajax.php', {action: 'cp_req', id : this.id}, function(response) {
			$("ul.cp_top").html(cpGetTop(response[0]));
			$("ul.cp_in").html(cpGetIn(response[1]));
		});
	});
	$(".widget.CatPath").on("click", "li.cp_parent a" , function() {
		$.getJSON('/wp-admin/admin-ajax.php', {action: 'cp_req', cp_up : true, id : this.id}, function(response) {
			$("ul.cp_top").html(cpGetTop(response[0]));
			$("ul.cp_in").html(cpGetIn(response[1]));
		});
	});
});

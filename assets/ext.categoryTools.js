$(function () {
    $('#jstree_demo_div').jstree({
		"core" : {
			"animation" : 0,
			"check_callback" : true,
			'force_text' : true,
			"themes" : { "stripes" : true },
			'data' : {
				'url' : mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=categorytools&method=read&format=json',
				dataType: "json"
			}
		},
		"plugins" : [ "wholerow" ]
    });
    $('#btn_rename').click(function(){
		var ref = $('#jstree_demo_div').jstree(true),
			sel = ref.get_selected();
		if(!sel.length) { return false; }
		sel = sel[0];
		ref.edit(sel);
	});
    $('#btn_delete').click(function(){
		var ref = $('#jstree_demo_div').jstree(true),
			sel = ref.get_selected();
		if(!sel.length) { return false; }
		ref.delete_node(sel);
	});
	$('#jstree_demo_div').on('changed.jstree', function(e, data){
		if(data.selected.length) {
			$('#cur_cat').html('with <a target="_blank" href="'+data.node.data.url+'">"'+data.node.text+'"</a> category');
			$('#btn_rename').prop('disabled', false);
			$('#btn_delete').prop('disabled', false);
		}else{
			$('#cur_cat').html('');
			$('#btn_rename').prop('disabled', true);
			$('#btn_delete').prop('disabled', true);
		}
	});
	$('#jstree_demo_div').on('rename_node.jstree', function(node, text, old) {
		// TODO: ...
		console.log(node, text, old);
	});
	$('#jstree_demo_div').on('delete_node.jstree', function(node, parent) {
		// TODO: ... parent = '#" for root nodes
		console.log(node, parent);
	});
});
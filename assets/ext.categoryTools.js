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
		"plugins" : [ "wholerow", "dnd" ],
		"dnd": {
			'is_draggable': function(nodes) {
				console.log(nodes);
				return true;
			},
            'large_drop_target': true,
            'copy': false
		}
    });

    $('#btn_rename').click(function(){
		var ref = $('#jstree_demo_div').jstree(true),
			sel = ref.get_selected();
		if(!sel.length) { return false; }
		sel = sel[0];
		ref.edit(sel);
	});

    $('#btn_delete').click(function(){

    	if(!confirm('Are you sure you want to delete the category? All pages assigned to the category will lost their assignment, all sub-categories will be deleted!')) {
    		return false;
		}

		var ref = $('#jstree_demo_div').jstree(true),
			sel = ref.get_selected();
		if(!sel.length) { return false; }
		ref.delete_node(sel);
	});

	$('#jstree_demo_div').on('changed.jstree', function(e, data){
		//console.log('changed.jstree');
		if(data.selected.length) {
			$('#cur_cat').html('with <a target="_blank" href="'+mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/index.php?title=Category:' + data.node.text+'">"'+data.node.text+'"</a> category');
			$('#btn_rename').prop('disabled', false);
			$('#btn_delete').prop('disabled', false);
		}else{
			$('#cur_cat').html('');
			$('#btn_rename').prop('disabled', true);
			$('#btn_delete').prop('disabled', true);
		}
	});

	$('#jstree_demo_div').on('rename_node.jstree', function(e, data) {

        var ref = $('#jstree_demo_div').jstree(true);

		// No need to do anything if there are no changes
		if( data.old === data.text ) {
			return false;
		}

		var mwTitle = mw.Title.newFromText(data.text, 10);
		if( !mwTitle || mwTitle.getRelativeText(10) === '' || mwTitle.getRelativeText(10).trim() !== data.text.trim() ) {
            ref.set_text(data.node, data.old);
		    alert('Illegal characters in provided title!');
		    return false;
        }

		// Get a confirmation from user
		if(!confirm('Please confirm category rename: "'+data.old+'" to "'+data.text+'" ?')) {
            ref.set_text(data.node, data.old);
			return false;
		}

		//
		if( !data.text.length ) {
			alert('Category name can\'t be empty string!');
            ref.set_text(data.node, data.old);
			return false;
		}

        $.post(mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=categorytools&method=rename&format=json',
            {
                'id': data.node.id,
				'new_category_name': data.text
            }, function(resp){
                console.log(resp);
                hideShadow();
                //ref.select_node(data.node, false, false, {});
				ref.deselect_all();
		});

		showShadow();
	});

	$('#jstree_demo_div').on('delete_node.jstree', function(e, data) {
		// TODO: ... parent = '#" for root nodes

		$.post(mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=categorytools&method=delete&format=json',
			{
				'id': data.node.id
			}, function(resp){
				console.log(resp);
				hideShadow();
		});

		showShadow();
	});

	$(document).on('dnd_stop.vakata', function (e, data) {

	    console.log(data);

	    var ref = $('#jstree_demo_div').jstree(true);
	    var droppedNode = ref.get_node(data.data.obj[0]);

        console.log('Node '+droppedNode.id+' was dropped!');
        if( droppedNode.parents.length > 1 ) {
            //console.log('Was dropped into another category!');
            var parentNode = ref.get_node(droppedNode.parent);
            //console.log('Parent: '+parentNode.id);

            // Sub-category actions
            $.post(mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=categorytools&method=make_subcategory&format=json',
                {
                    'id': droppedNode.id,
                    'parent': parentNode.id
                }, function(resp){
                    console.log(resp);
                    hideShadow();
                    ref.refresh(); // it's necessary to refresh tree in order to prevent conflicts when same category is a child of many root categories
                });

            showShadow();

        }else{
            //console.log('Was dropped at root!');

            // Root category actions
            $.post(mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=categorytools&method=make_root&format=json',
                {
                    'id': droppedNode.id
                }, function(resp){
                    console.log(resp);
                    hideShadow();
                    ref.refresh(); // see above
                });

            showShadow();

        }

    });


	function showShadow() {
		$('#shadow').css('display', 'flex');
	}

	function hideShadow() {
		$('#shadow').hide();
	}

});
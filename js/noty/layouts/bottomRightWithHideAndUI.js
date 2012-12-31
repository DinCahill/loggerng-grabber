;(function($) {
	// atemptied modification of a default layout to add a hide ability, and some jquery ui theme
	$.noty.layouts.bottomRight = {
		name: 'bottomRight',
		options: { // overrides options
			
		},
		container: {
			object: '<ul id="noty_bottomRight_layout_container" />',
			selector: 'ul#noty_bottomRight_layout_container',
			style: function() {
				
				$(this).addClass("ui-state-default ui-icon-before ui-icon-before-leftCenter ui-icon-before-triangle-1-e ui-corner-all");
				
				$(this).css({
					bottom: 20,
					right: 0,
					position: 'fixed',
					width: '330px',
					height: 'auto',
					margin: 0,
					padding: 0,
					paddingLeft: 16,
					listStyleType: 'none',
					zIndex: 10000000,
					border: '0px none',
					overflow: 'hidden'
				});

				if (window.innerWidth < 600) {
					$(this).css({
						right: 5
					});
				}
				
				$(this).off(".notyHider").on({
					"click.notyHider":function(e) {
						if(this != e.target)
						{
							return;
						}
						$(this).stop(true, true).animate({width:($(this).hasClass("ui-icon-before-triangle-1-e")?0:330)},"fast","swing").toggleClass("ui-icon-before-triangle-1-e ui-icon-before-triangle-1-w ui-state-active");
					},
					"mouseover.notyHider":function(e) {
						if(this != e.target)
						{
							return;
						}
						$(this).addClass("ui-state-hover");
					},
					"mouseout.notyHider":function(e) {
						if(this != e.target)
						{
							return;
						}
						$(this).removeClass("ui-state-hover");
					}
				});
			}
		},
		parent: {
			object: '<li />',
			selector: 'li',
			css: {}
		},
		css: {
			display: 'none',
			width: '100%'
		},
		addClass: ''
	};

})(jQuery);
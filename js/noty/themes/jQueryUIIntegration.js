;(function($) {
	// many things need replacing to fit in with a jquery ui theme
	$.noty.themes.defaultTheme = {
		name: 'defaultTheme',
		helpers: {
			borderFix: function() {
				if (this.options.dismissQueue) {
					var selector = this.options.layout.container.selector + ' ' + this.options.layout.parent.selector;
					switch (this.options.layout.name) {
						case 'top':
							$(selector).removeClass("ui-corner-bottom");
							$(selector).last().addClass("ui-corner-bottom"); break;
						case 'topCenter': case 'topLeft': case 'topRight':
						case 'bottomCenter': case 'bottomLeft': case 'bottomRight':
						case 'center': case 'centerLeft': case 'centerRight': case 'inline':
							$(selector).removeClass("ui-corner-bottom ui-corner-top");
							$(selector).first().addClass("ui-corner-top");
							$(selector).last().addClass("ui-corner-bottom"); break;
						case 'bottom':
							$(selector).removeClass("ui-corner-top");
							$(selector).first().addClass("ui-corner-top"); break;
						default: break;
					}
				}
			}
		},
		modal: {
			css: {
				position: 'fixed',
				width: '100%',
				height: '100%',
				backgroundColor: '#000',
				zIndex: 10000,
				opacity: 0.6,
				display: 'none',
				left: 0,
				top: 0
			}
		},
		style: function() {
		
			this.$bar.css({
				overflow: 'hidden'//,
				//background: "url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABsAAAAoCAYAAAAPOoFWAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAPZJREFUeNq81tsOgjAMANB2ov7/7ypaN7IlIwi9rGuT8QSc9EIDAsAznxvY4pXPKr05RUE5MEVB+TyWfCEl9LZApYopCmo9C4FKSMtYoI8Bwv79aQJU4l6hXXCZrQbokJEksxHo9KMOgc6w1atHXM8K9DVC7FQnJ0i8iK3QooGgbnyKgMDygBWyYFZoqx4qS27KqLZJjA1D0jK6QJcYEQEiWv9PGkTsbqxQ8oT+ZtZB6AkdsJnQDnMoHXHLGKOgDYuCWmYhEERCI5gaamW0bnHdA3k2ltlIN+2qKRyCND0bhqSYCyTB3CAOc4WusBEIpkeBuPgJMAAX8Hs1NfqHRgAAAABJRU5ErkJggg==') repeat-x scroll left top #fff"
			});

			this.$message.css({
				fontSize: '13px',
				lineHeight: '16px',
				textAlign: 'center',
				padding: '8px 10px 9px',
				width: 'auto',
				position: 'relative'
			});
			
			this.$closeButton.addClass("ui-state-error-text");

			this.$closeButton.css({
				position: 'absolute',
				top: -2, right: -2,
				display: 'none',
				cursor: 'pointer'
			});
			
			this.$closeButton.append("<div class='ui-icon ui-icon-circle-close'></div>");
			
			this.$buttons.css({
				padding: 5,
				textAlign: 'right',
				borderTop: '1px solid #ccc',
				backgroundColor: '#fff'
			});

			this.$buttons.find('button').css({
				marginLeft: 5
			});

			this.$buttons.find('button:first').css({
				marginLeft: 0
			});

			this.$bar.bind({
				mouseenter: function() { $(this).find('.noty_close').stop(true, true).fadeIn(); },
				mouseleave: function() { $(this).find('.noty_close').stop(true, true).delay(500).fadeOut(); }
			});

			switch (this.options.layout.name) {
				case 'top':
					this.$bar.css({
						borderBottom: '2px solid #eee',
						borderLeft: '2px solid #eee',
						borderRight: '2px solid #eee',
						boxShadow: "0 2px 4px rgba(0, 0, 0, 0.1)"
					}).addClass("ui-corner-bottom");
				break;
				case 'topCenter': case 'center': case 'bottomCenter': case 'inline':
					this.$bar.css({
						border: '1px solid #eee',
						boxShadow: "0 2px 4px rgba(0, 0, 0, 0.1)"
					}).addClass("ui-corner-bottom ui-corner-top");
					this.$message.css({fontSize: '13px', textAlign: 'center'});
				break;
				case 'topLeft': case 'topRight':
				case 'bottomLeft': case 'bottomRight':
				case 'centerLeft': case 'centerRight':
					this.$bar.css({
						border: '1px solid #eee',
						boxShadow: "0 2px 4px rgba(0, 0, 0, 0.1)"
					}).addClass("ui-corner-bottom ui-corner-top");
					this.$message.css({fontSize: '13px', textAlign: 'left'});
				break;
				case 'bottom':
					this.$bar.css({
						borderTop: '2px solid #eee',
						borderLeft: '2px solid #eee',
						borderRight: '2px solid #eee',
						boxShadow: "0 -2px 4px rgba(0, 0, 0, 0.1)"
					}).addClass("ui-corner-top");
				break;
				default:
					this.$bar.css({
						border: '2px solid #eee',
						boxShadow: "0 2px 4px rgba(0, 0, 0, 0.1)"
					});
				break;
			}

			switch (this.options.type) {
				case 'alert': case 'notification':
					this.$bar.addClass('ui-state-highlight');
					this.$bar.css({backgroundColor: '', borderColor: '', color: ''});
					break;
				case 'warning':
					//requires more ui intergration
					this.$bar.css({backgroundColor: '#FFEAA8', borderColor: '#FFC237', color: '#826200'});
					this.$buttons.css({borderTop: '1px solid #FFC237'}); break;
				case 'error':
					this.$bar.addClass('ui-state-error');
					this.$bar.css({backgroundColor: '', borderColor: '', color: ''});
					this.$message.css({fontWeight: ''});
					this.$buttons.css({borderTop: ''}); break;
				case 'information':
					this.$bar.addClass('ui-state-hover');
					this.$bar.css({backgroundColor: '', borderColor: '', color: ''});
					this.$buttons.css({borderTop: ''}); break;
				case 'success':
					//requires more ui intergration
					this.$bar.css({backgroundColor: 'lightgreen', borderColor: '#50C24E', color: 'darkgreen'});
					this.$buttons.css({borderTop: '1px solid #50C24E'});break;
				default:
					this.$bar.css({backgroundColor: '#FFF', borderColor: '#CCC', color: '#444'}); break;
			}
		},
		callback: {
			onShow: function() { $.noty.themes.defaultTheme.helpers.borderFix.apply(this); },
			onClose: function() { $.noty.themes.defaultTheme.helpers.borderFix.apply(this); }
		}
	};

})(jQuery);

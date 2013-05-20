<?php require_once "shibbobleh_client.php" ?>
<!DOCTYPE html> 
<html>
	<head>
		<title>MELON Logger</title>
		<!-- welcome to MELON - backronym pending -->
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<script type="text/javascript" src="js/jquery-1.8.2.min.js"></script>
		<script type="text/javascript">
			WebFontConfig = {
				google: { families: [ 'Open+Sans::latin' ] },
				active:function(){
					// the calendar has problems with drawing the google font initially, so redraw it
					showDisplayer.fullCalendar('render');
					updateStartEndCal();
				}
			};
			
			(function() {
				var wf = document.createElement('script');
				wf.src = ('https:' == document.location.protocol ? 'https' : 'http') +
					'://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
				wf.type = 'text/javascript';
				wf.async = 'true';
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(wf, s);
			})();
		</script>
		
		<link rel="stylesheet" type="text/css" href="css/fullcalendar.css" />
		
		<script type="text/javascript" src="js/heartcode-canvasloader.js"></script>
		
		<link rel="stylesheet" type="text/css" href="css/ui-darkness/jquery-ui-1.9.0.custom.min.css" />
		<link rel="stylesheet" type="text/css" href="css/jquery.ui.selectmenu.css" />
		
		<link rel="stylesheet" type="text/css" href="css/ui-corrections.css" />
		
		<script type="text/javascript" src="js/jquery-ui-1.9.0.custom.min.hilight.fix.js"></script>
		<script src="js/jquery-ui-timepicker-addon.js"></script>
		<script type="text/javascript" src="js/fullcalendar.min.js"></script>
		<script type="text/javascript" src="js/date-en-GB.js"></script>
		<script type="text/javascript" src="js/date-extras.js"></script>
		
		<script type="text/javascript" src="js/jquery.ui.selectmenu.js"></script>
		
		<!-- You know what we need. More plugins! -->
		<script type="text/javascript" src="js/noty/jquery.noty.js"></script>
		<script type="text/javascript" src="js/noty/layouts/bottomRightWithHideAndUI.js"></script>
		<script type="text/javascript" src="js/noty/themes/jQueryUIIntegration.js"></script>
		
		
		<script type="text/javascript">
			// a constants
			var minTime = 61*60000;
			
			// a multitude of widgets
			var showDisplayer = null;
			var startDateTextBox = null;
			var endDateTextBox = null;
			var eventCheck = null;
			var throbber = null;
			var fullFileName = null;
			
			var redrawTimer = null;

			var openRequests = {};
			var possiblyFinished = {};
			
			var lastBox = {};
			
			$(function(){
				$.noty.defaults.layout = 'bottomRight';
				$.noty.defaults.closeWith = ["button"];
				
				// calendar
				showDisplayer = $("#calendar").fullCalendar({
					header: {
						left: "prev,next title",
						center: "",
						right: "today"
						},
					timeFormat: 'H:mm',
					axisFormat: 'HH:mm',
					height:700,
					defaultView: "agendaWeek",
					allDaySlot: false,
					slotMinutes: 60,
					ignoreTimezone:false,
					selectable:true,
					firstDay:(new Date().getDay()+1)%7, // set the current day to the far right so the full week can be seen
					theme: true,
					allDayDefault: false,
					lazyFetching:true,
					unselectAuto:false, // dont clear the selection. Ever.
					
					// on a calendar selection validate that the time is not in the past and update the time selectors
					select:function( startDate, endDate, allDay, jsEvent, view ) {
						if(endDate>(new Date())) // validate
						{
							endDate=new Date()
							startDate = startDate>=endDate?(1).minute().ago():startDate;
							updateStartEndCal(startDate, endDate);
						}
						updateStartEndBox(startDate, endDate); // update
					},
					
					// if an event is clicked then select it
					eventClick: function(calEvent, jsEvent, view) {
						updateStartEndCal(calEvent.start, calEvent.end);
					},
					
					// show a loading 'throbber' when making an ajax request
					loading: function(isLoading, view){
						if(!throbber) {return;}
						// isLoading?throbber.fadeIn(100):throbber.fadeOut(100);
						throbber.toggle(isLoading);
					},
					
					eventAfterRender: function(event, element) {
						element.prop("title","");
						element.tooltip({
							content: event.title,
							hide:100,
							show: {
								delay: 750
							}
						});
						
					},
					
					events:'/laconia/range/schedule/timeslot/' // default event
				});
				
				// time pickers
				
				var tmp = $.timepicker._controls.slider.create;
				$.timepicker._controls.slider.create = function(tp_inst, obj, unit, val, min, max, step) {
					var x = tmp(tp_inst, obj, unit, val, min, max, step);
					var y = x.slider("option","stop");
					return x.slider("option","stop", function(event, ui) {
						lastBox[unit] = tp_inst;
						y(event, ui);
					});
				}
				
				// time picker for the start of selection
				startDateTextBox = $('#startTime').datetimepicker({
					/* hideCalendar is a custom property!
					 * The built in method for removing the calendar part of the
					 * datetimepicker causes it to only remember the time, and
					 * not the date, this is needed because otherwise maxDateTime
					 * limits the time for all days */
					hideCalendar: true, 
					altFieldTimeOnly: false,
					showButtonPanel: false, // the buttons pannel only has 'today' on it
					timeOnlyTitle: "Start Time", // this gets replaced with the current date
					maxDateTime: (1).minute().ago(), 
					
					// when time is altered make a test to see if it is grater than the end time
					// if so alter the end time. Then update everything
					onSelect: function(dateText, inst) {
						if (endDateTextBox.val() != '') {
							var testStartDate = startDateTextBox.datetimepicker('getDate');
							var testEndDate = endDateTextBox.datetimepicker('getDate');
							if (testStartDate >= testEndDate)
							{
								var alteredDate = testStartDate.set({
									hour: lastBox.hour?lastBox.hour.hour:null,
									minute: lastBox.minute?lastBox.minute.minute:null
								});
								
								// test if the minuet was not set by the current instace 
								if((lastBox.minute||inst) != inst) // equivelent to lastBox.minute != inst && lastBox.minute
								{
									alteredDate.add(-1).minute();
								}
								
								startDateTextBox.datetimepicker('setDate', alteredDate);
								endDateTextBox.datetimepicker('setDate', alteredDate.add(1).minute());
								
								/*if(lastBox == endDateTextBox)
								{
								//	startDateTextBox.datetimepicker('setDate', testEndDate.add(-1).minute());
								}
								else
								{
									endDateTextBox.datetimepicker('setDate', testStartDate.add(1).minute());
								}*/
							}
						}
						else {
							endDateTextBox.val(dateText);
						}
						//updateStartEndBox();
						updateStartEndCal();
					}
				});
				
				// time picker for the start of selection
				endDateTextBox = $('#endTime').datetimepicker({
					hideCalendar: true, 
					altFieldTimeOnly: false,
					showButtonPanel: false,
					timeOnlyTitle: "End Time",
					maxDateTime: (new Date()),
					
					// as startDateTextBox, only if it's less than the start time
					onSelect: function(dateText, inst) {
						if (startDateTextBox.val() != '') {
							var testStartDate = startDateTextBox.datetimepicker('getDate');
							var testEndDate = endDateTextBox.datetimepicker('getDate');
							if (testStartDate >= testEndDate)
							{
								var alteredDate = testEndDate.set({
									hour: lastBox.hour?lastBox.hour.hour:null,
									minute: lastBox.minute?lastBox.minute.minute:null
								});
								
								// test if the minuet was not set by the current instace 
								if((lastBox.minute||inst) != inst) // equivelent to lastBox.minute != inst && lastBox.minute
								{
									alteredDate.add(1).minute();
								}
								
								endDateTextBox.datetimepicker('setDate', alteredDate);
								startDateTextBox.datetimepicker('setDate', alteredDate.add(-1).minute());
								
								/*if(lastBox == startDateTextBox)
								{
								//	endDateTextBox.datetimepicker('setDate', testStartDate.add(1).minute());
								}
								else
								{
									startDateTextBox.datetimepicker('setDate', testEndDate.add(-1).minute());
								}*/
							}
						}
						else {
							startDateTextBox.val(dateText);
						}
						//updateStartEndBox();
						updateStartEndCal();
					}
				});
				
				// set initial dates to the past hour
				endDateTextBox.datetimepicker('setDate', (new Date()));
				startDateTextBox.datetimepicker('setDate', (1).hour().ago());
				updateStartEndCal();
				
				// events dropdown
				
				showDisplayer.find(".fc-header-right .ui-corner-left").removeClass("ui-corner-left");
				
				eventCheck = $(document.createElement("select"));
				
				// this is an array of objects for easily editing the dropdown menu
				var options = [
					{label:"Shows", select:true, event:"/laconia/range/schedule/timeslot/"},
					{label:"Requests", event:"lemon.php?action=getrequests"},
					{label:"Shows", event:"/laconia/range/schedule/timeslot/", view:"basicWeek", viewName:"Lists"},
					{label:"Requests", event:"lemon.php?action=getrequests", view:"basicWeek", viewName:"Lists"},
					{label:"None"}
					];
				
				for(var i = 0; i < options.length; i++)
				{
					if(!options[i].label) {continue;} // skip empty options
					
					var atr = ' value="'+(options[i].event||"")+'"';
					
					atr += options[i].select?' selected="selected"':"";

					// this source has a different view
					if(options[i].view)
					{
						options[i].viewName=options[i].viewName||"";
						
						// find the optgroup with that view and name
						var opt = eventCheck.children("optgroup[title=\""+options[i].view+"\"][label=\""+options[i].viewName+"\"]");
						if(!opt.length)
						{
							// oh dear it doesen't exit :(
							opt = $("<optgroup label=\""+(options[i].viewName?options[i].viewName:"")+"\" title=\""+options[i].view+"\"></optgroup>").appendTo(eventCheck);
						}
						// add it to the optgroup
						opt.append("<option"+atr+">"+options[i].label+"</option>");

					}
					else
					{
						// if it doesent have an optgroup, and there are optgroups then add it before them
						if(eventCheck.children("optgroup:first").length)
						{
							$("<option"+atr+">"+options[i].label+"</option>").insertBefore(eventCheck.children("optgroup:first")[0]);
						}
						else
						{
							eventCheck.append("<option"+atr+">"+options[i].label+"</option>");
						}
					}
					
				}
				
				showDisplayer.find(".fc-header-right").prepend(eventCheck); // add the select to the calendar
				
				// make the select a UI selectmenu
				eventCheck.selectmenu({
					menuWidth: 120,
					
					// the menu tries to re-round all its corners when ever it opens or closes.
					close:function() {
						showDisplayer.find(".fc-header-right .ui-selectmenu").removeClass( "ui-corner-all" ).addClass("ui-corner-left");
					},
					open:function() {
						showDisplayer.find(".fc-header-right .ui-selectmenu").removeClass( "ui-corner-top ui-corner-left" );
					},
					
					change:updateEventCheck
				});
				showDisplayer.find(".fc-header-right .ui-selectmenu").removeClass( "ui-button ui-corner-all" ).addClass( "fc-button ui-corner-left ui-corner-tl" ).css("width", "120px");
				
				// throbber - that circular spiny thing that tells you something is happening
				throbber = $('<span id="throbberCont"></span>');
				showDisplayer.find(".fc-header-right").prepend(throbber);
				throbber.hide();
				
				var throbberCan = new CanvasLoader('throbberCont', {useParent:true});
				throbberCan.setColor('#f58300'); // default is '#000000'
				throbberCan.setDiameter(25); // default is 40
				
				throbber.children().css({'padding-right': '5px', 'padding-top': '5px'});
				
				// buttons
				
				// a select menu for audio file type
				$("#fileType").selectmenu({
						close:function() {
							$("#fileType").selectmenu("widget").find(".ui-selectmenu").removeClass( "ui-corner-all" ).addClass("ui-corner-right");
						},
						open:function() {
							$("#fileType").selectmenu("widget").find(".ui-corner-top").removeClass( "ui-corner-top ui-corner-right" ).addClass("ui-corner-tr");
						}
					}
				);
				
				// ajust the text in the file type dropdown to align with the text in the file name input
				$("#fileType").selectmenu("widget").find(".ui-selectmenu-status").css({"padding-left":"5px","padding-top":"0.4em"});
				
				// "$("#file").buttonset();" brakes things so manualy do it
				$("#file").addClass("ui-buttonset");
				
				// the wrapper button for the file name input +	extra css + give focus
				$("#fileNameCont").button().css({"vertical-align":"top", "cursor":"text","margin-right":"-3px"}).click(function(){$("#fileName").focus();});
				
				// remove left and right padding on everything in in the button
				$("#fileNameCont *").css({"padding-left":"0px","padding-right":"0px"});
				// the text box for inputting a file name's extra css
				$("#fileName").css({"height":"1.3em","width": "165px","padding":"0px","border":"0px","margin-left":"0px","margin-right":"0px", "background-color":"rgba(255,255,255,0.6)"});
				
				// remove rounded corners from all the buttons
				$("#file .ui-corner-all").removeClass( "ui-corner-all" );
				
				//make the request button a button!
				$("#logRequest").button().click(function(e) {
					e.preventDefault();
					createLogRequest();
				});
				
				// add left and right rounded corners to the first and last button
				$("#file fieldset .ui-widget:first").addClass("ui-corner-left");
				$("#file fieldset .ui-widget:last").addClass("ui-corner-right");
				
				// the time values for the buttons
				var timeButtonSelection = [1,3,5,10];
				
				for(var i = 0; i < timeButtonSelection.length; i++)
				{
					// the add and subtract buttons, the ternery statments give the first and last elements texts
					var a = $("<div>"+(i==0?"<span style=\"margin-right:6px;\">Add</span>":"")+timeButtonSelection[i]+(i+1==timeButtonSelection.length?"<span style=\"margin-left:6px;\">minutes</span>":"")+"</div>");
					var b = $("<div>"+(i==0?"<span style=\"margin-right:6px;\">Sub</span>":"")+timeButtonSelection[i]+(i+1==timeButtonSelection.length?"<span style=\"margin-left:6px;\">minutes</span>":"")+"</div>");
					
					// these squiggles of code make the buttons, and give them a static click function.
					$("#timeAddButtons").append(a);
					a.button().click($.proxy(function(n) {
						updateStartEndBox(startDateTextBox.datetimepicker('getDate').add(-n).minutes(), endDateTextBox.datetimepicker('getDate').add(n).minutes());
						updateStartEndCal();
					}, a, timeButtonSelection[i]));
					
					$("#timeSubButtons").append(b);
					b.button().click($.proxy(function(n) {
						updateStartEndBox(startDateTextBox.datetimepicker('getDate').add(-n).minutes(), endDateTextBox.datetimepicker('getDate').add(n).minutes());
						updateStartEndCal();
					}, b, -timeButtonSelection[i]));
					
				}
				
				// alter the buttons to make them less spaced.
				timeButtonSelection = $("#timeAddButtons").buttonset().css("marginRight",4).find(".ui-button-text");
				timeButtonSelection.not(":first").css("paddingLeft",5);
				timeButtonSelection.not(":last").css("paddingRight",5).parent().css("marginRight",-1);
				
				timeButtonSelection = $("#timeSubButtons").buttonset().find(".ui-button-text");
				timeButtonSelection.not(":first").css("paddingLeft",5);
				timeButtonSelection.not(":last").css("paddingRight",5).parent().css("marginRight",-1);
				
				// filename helper functions
				fullFileName = function(){ return fullFileName.fileName()+"."+fullFileName.fileExtention();};
				
				fullFileName.fullTimeName = function(){return fullFileName.fileName.timeName()+"."+fullFileName.fileExtention();};

				fullFileName.fileName = function(){return fullFileName.fileName.raw()||fullFileName.fileName.timeName();};

				fullFileName.fileName.raw = function(){return $("#fileName").val();};
				
				fullFileName.fileName.timeName = function(){return fullFileName.fileName.timeName.start()+"-"+fullFileName.fileName.timeName.end();};
				
				fullFileName.fileName.timeName.start = function(){return startDateTextBox.datetimepicker('getDate').format("U");};
				
				fullFileName.fileName.timeName.end = function(){return endDateTextBox.datetimepicker('getDate').format("U");};
				
				fullFileName.fileExtention = function(){return $("#fileType").val();};
				
				// fetch the inital list of ongoing progresses.
				updateProgress();
				
				// update the availible max time
				setInterval(function(){ updateStartEndBox();},60000);

				setInterval(function(){ if(!$.isEmptyObject(openRequests)) {updateProgress();}},5000);
				
				// does a thing. shhhhhh.
				$("#s").click(function() {
					$("body").css("background-image", "url(http://bcchang.com/immersive_blog/wp-content/uploads/2009/10/fieldstone-c.jpg)");
				})
			});
			
			// some general utility functions
			
			// remove the calendar's old source of events and (if specified) add a new one
			function updateEventCheck(index, option)
			{
				/* this is a custom alteration!
				 * normaly removeEventSource requires a paramater of what event source to remove,
				 * however it has been altered so if none is given it will remove all event sources */
				showDisplayer.fullCalendar('removeEventSource');
				if(option.value)
				{
					// does it have a different view? If so it will be in an optgroup
					var opt = $(option.option).parent("optgroup");
					if(opt.length)
					{
						showDisplayer.fullCalendar( 'changeView', opt.attr("title"));
					}
					else
					{
						showDisplayer.fullCalendar( 'changeView', "agendaWeek" );
					}
					showDisplayer.fullCalendar( 'addEventSource', option.value ); // add the new event source
				}
			}
			
			//update the start and end datetimepickers
			function updateStartEndBox(start, end)
			{
				if(start || end)
				{
				//	lastBox = {}
				}
				
				// if no start or end time are passed in then default to the current
				start=start||startDateTextBox.datetimepicker('getDate');
				end=end||endDateTextBox.datetimepicker('getDate');
				
				// prevent a bug
				if(end<=start) {return;}
				
				// update the max time of the start datetimepicker to one minute ago
				// (to maintain a gap of 1 minute between the start and end times)
				startDateTextBox.datetimepicker('option', 'maxDateTime', (1).minute().ago());
				
				// update the text next to the time of the start datetimepicker to the current selected date
				startDateTextBox.datetimepicker('option', 'timeText', start.format("%a %eS %b %Y"));
				
				// update the max time of the end datetimepicker to the current
				endDateTextBox.datetimepicker('option', 'maxDateTime', new Date());
				
				// update the text next to the time of the end datetimepicker to the current selected date
				endDateTextBox.datetimepicker('option', 'timeText', end.format("%a %eS %b %Y"));
				
				// now change the date and time
				startDateTextBox.datetimepicker('setDate', start);
				endDateTextBox.datetimepicker('setDate', end);
				
				$("#fileName").attr("placeholder", start.format("U")+"-"+end.format("U"));
			}
			
			// selects the inputed time (defaulting to the datetimepickers when no specified)
			function updateStartEndCal(start, end)
			{
				showDisplayer.fullCalendar( 'select', start||startDateTextBox.datetimepicker('getDate'), end||endDateTextBox.datetimepicker('getDate'), false );
			}
			
			// create a log request
			function createLogRequest()
			{
				jQuery.ajax("lemon.php?action=make&start="
							+ startDateTextBox.datetimepicker('getDate').format("U")
							+ "&end=" + endDateTextBox.datetimepicker('getDate').format("U")
							+ "&format=" + fullFileName.fileExtention()
							+ "&title=" + fullFileName.fileName.raw());
				var id = fullFileName.fileName.timeName()+fullFileName.fileExtention();
				if($("#"+id).length)
				{
					return;
				}
				
				var elem = $('<div id="'+id+'"><span class="caption">'+fullFileName()+'</span></div>');
				
				if(!openRequests[id])
				{
					openRequests[id] = {
						file : fullFileName(),
						start : fullFileName.fileName.timeName.start(),
						end : fullFileName.fileName.timeName.end(),
						format : fullFileName.fileExtention(),
						progress : 0
					};
				}
				openRequests[id].e = elem;
				
				openRequests[id].n = noty({text: elem, callback: { onClose:jQuery.proxy( function() {
						delete this.e;
						delete this.n;
				}, openRequests[id])}});
				elem.progressbar({value: openRequests[id].progress});
			}

			function updateProgress()
			{
				$.get( "lemon.php?action=allprogress", undefined, undefined, "json").done(function(data) {
					
					// make a shallow copy of the requests
					possiblyFinished = jQuery.extend({}, openRequests);
					
					while(data.length)
					{
						var n = data.pop();
						if(!(n.start&&n.end&&n.format)) {continue;}
						var id = n.start+"-"+n.end+n.format;
						if(!openRequests[id])
						{
							var filename = (n.title || (n.start + "-" + n.end)) + '.' + n.format;
							var elem = $('<div id="'+id+'"><span class="caption">'+filename+'</span></div>');
							openRequests[id] = {
								start : n.start,
								end : n.end,
								format : n.format,
								file : filename,
								e : elem,
								n : noty({text: elem, callback: { onClose:jQuery.proxy( function() { delete this.e; delete this.n;}, openRequests[id])}})
							}
							elem.progressbar({value: openRequests[id].progress});
						}
						
						openRequests[id].progress = n.progress;
						$(openRequests[id].e).progressbar("option", {value: n.progress});
						
						// delete!
						delete possiblyFinished[id];
					}
					
					$.each(possiblyFinished, function(k, v) {
						if(!(v.start&&v.end&&v.format)) {return 1;} // continue
						
						$.get("lemon.php?action=progress"
							+ "&start=" + v.start
							+ "&end=" + v.end
							+ "&format=" + v.format, undefined, undefined, "text").done(function(data) {
								var d = parseInt(data);
								if(d==100)
								{
									var tx = '<div id="'+k+'"><a href="lemon.php?action=download&start='+openRequests[k].start+'&end='+openRequests[k].end+'&format='+openRequests[k].format+'">Download '+openRequests[k].file+'</a><br><span style="font-size:55%;">(From: '+(new Date(parseInt(openRequests[k].start)*1000)).format("%a %eS %b %Y %T")+' To: '+(new Date(parseInt(openRequests[k].end)*1000)).format("%a %eS %b %Y %T")+')</span></div>';
									if(openRequests[k].n)
									{
										openRequests[k].n.setText(tx);
									}
									else
									{
										noty({text: tx});
									}
									delete openRequests[k];
									clearTimeout(redrawTimer);
									redrawTimer = setTimeout(function(){showDisplayer.fullCalendar('refetchEvents');}, 1000);
								}
								else if(d<0||d>100)
								{
									if(openRequests[k].n)
									{
										openRequests[k].n.close();
									}
									delete openRequests[k];
								}
								else 
								{
									openRequests[k].progress = d;
									$(openRequests[k].e).progressbar("option", {value: d});
								}
							});
					});
				});
			}
			// HTML follows
		</script>
	</head>
	<body>
		<div id="s" style="width:1px; height:1px; position:absolute; top:0px; left:0px;"></div>
		<div id="cont" style="margin-right:auto; margin-left:auto; width:1050px; font-size: 160%;">
			<span id="startTime" style="display:inline-block; width:33%; vertical-align: top;"></span>
			<span id="endTime" style="display:inline-block; width:33%; vertical-align: top;"></span>
			
			<div id="buttons" style="display:inline-block; width:33%; vertical-align: top;">
				<form>
					<div id="file" class="small-gap">
						<fieldset>
							<div id="fileNameCont">
								<input type="text" id="fileName" name="fileName" maxlength="64">
							</div>
							<select id="fileType" name="fileType">
								<option value="mp3" selected="selected">.mp3</option>
								<option value="flac">.flac</option>
								<option value="ogg">.ogg</option>
							</select>
						</fieldset>
						<input id="logRequest" type="submit" value="Make Request">
					</div>					
					<div id="timeButtons" class="small-gap">
						Alter time by:
						<br>
						<fieldset id="timeAddButtons"></fieldset>
						<fieldset id="timeSubButtons"></fieldset>
					</div>
				</form>
			</div>
			
			<br>
			<br>
			<div id="calendar" style=""></div>
		</div>
	</body>
</html>

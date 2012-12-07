<!DOCTYPE html> 

<html>
	<head>
		<title>logger</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<script type="text/javascript" src="js/jquery-1.8.2.min.js"></script>
		<script type="text/javascript">
			WebFontConfig = {
				google: { families: [ 'Open+Sans::latin' ] },
				active:function(){
					// the calendar has problems with drawing the google font initily, so redraw it
					showDisplayer.fullCalendar('render');
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
		
		<script type="text/javascript">
			// a constants
			var minTime = 61*60000;
			
			// a multitude of widgets
			var showDisplayer = null;
			var startDateTextBox = null;
			var endDateTextBox = null;
			var eventCheck = null;
			var throbber = null;
			
			$(function(){
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
					
					events:'/laconia/range/schedule/timeslot/' // default event
				});
				
				// time pickers
				
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
								endDateTextBox.datetimepicker('setDate', testStartDate.add(1).minute());
						}
						else {
							endDateTextBox.val(dateText);
						}
						updateStartEndBox();
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
								startDateTextBox.datetimepicker('setDate', testEndDate.add(-1).minute());
						}
						else {
							startDateTextBox.val(dateText);
						}
						updateStartEndBox();
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
					{label:"None"},
					{label:"Requests"},
					{label:"Shows", select:true, event:"/laconia/range/schedule/timeslot/"}
					];
				
				for(var i = 0; i < options.length; i++)
				{
					if(!options[i].label) {continue;} // skip empty options
					
					var atr = ' value="'+(options[i].event||"")+'"';
					
					atr += options[i].select?' selected="selected"':"";
					
					eventCheck.prepend("<option"+atr+">"+options[i].label+"</option>");
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
				
				// the wrapper button for the file name input +  extra css + give focus
				$("#fileNameCont").button().css({"vertical-align":"top", "cursor":"text","margin-right":"-3px"}).click(function(){$("#fileName").focus();});
				
				// remove left and right padding on everything in in the button
				$("#fileNameCont *").css({"padding-left":"0px","padding-right":"0px"});
				// the text box for inputting a file name's extra css
				$("#fileName").css({"height":"1.3em","width": "150px","padding":"0px","border":"0px","margin-left":"0px","margin-right":"0px", "background-color":"rgba(255,255,255,0.6)"});
				
				// remove rounded corners from all the buttons
				$("#file .ui-corner-all").removeClass( "ui-corner-all" );
				
				// add left and right rounded corners to the first and last button
				$("#file :first").addClass("ui-corner-left");
				$("#file :last").addClass("ui-corner-right");
				
				// does a thing. shhhhhh.
				$("#s").click(function() {
					$("body").css("background-image", "url(http://bcchang.com/immersive_blog/wp-content/uploads/2009/10/fieldstone-c.jpg)");
				})
				
				// progress
				$( "#progressbar" ).progressbar({value: 37});
				$( "#progressbar1" ).progressbar({value: 37});
				$( "#progressbar2" ).progressbar({value: 37});
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
					showDisplayer.fullCalendar( 'addEventSource', option.value ); // add the new event source
				}
			}
			
			//update the start and end datetimepickers
			function updateStartEndBox(start, end)
			{
				// if no start or end time are passed in then default to the current
				start=start||startDateTextBox.datetimepicker('getDate');
				end=end||endDateTextBox.datetimepicker('getDate');
				
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
			// HTML follows
		</script>
	</head>
	<body>
		<div id="s" style="width:1px; height:1px; position:absolute; top:0px; left:0px;"></div>
		<div style="margin-right:auto; margin-left:auto; width:1050px; font-size: 160%;">
			<span id="startTime" style="display:inline-block; width:33%; vertical-align: top;"></span>
			<span id="endTime" style="display:inline-block; width:33%; vertical-align: top;"></span>
			
			<div id="buttons" style="display:inline-block; width:33%; vertical-align: top;">
				<div id="file">
					<div id="fileNameCont">
						<input type="text" id="fileName" name="fileName" maxlength="64"></input>
					</div>
					<select id="fileType" name="fileType">
						<option value=".mp3" selected="selected">.mp3</option>
						<option value=".flac">.flac</option>
						<option value=".ogg">.ogg</option>
					</select>
				</div>
			
			</div>
			<br>
			<br>
			<div id="progressbar"><span class="caption">Loading...please wait</span></div>
			<div id="progressbar1"><span class="caption">Loading...please wait</span></div>
			<div id="progressbar2"><span class="caption">Loading...please wait</span></div>
			<br>
			
			<div id="calendar" style=""></div>
		</div>
	</body>
</html>
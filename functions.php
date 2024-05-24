<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect him to notLoggedIn page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: notLoggedIn.php");
    exit;
}

// Include the database config file
require_once 'dbConfig.php';
// Load function based on the request
if(isset($_POST['func']) && !empty($_POST['func'])){
	switch($_POST['func']){
		case 'getCalender':
			getCalender($_POST['year'],$_POST['month']);
			break;
		case 'getEvents':
			getEvents($_POST['date']);
			break;
		case 'addEvent':
			addEvent($_POST);
			break;
		case 'deleteEvent':
			deleteEvent($_POST);
			break;
		case 'updateEvent':
			updateEvent($_POST);
			break;
		default:
			break;
	}
}

// main function that gets called based on the request above
function getCalender($year = '', $month = ''){
	$username = htmlspecialchars($_SESSION["username"]);
	$shareusername = "";
	$dateYear = ($year != '')?$year:date("Y"); //date() php function formats a unix timestamp and Y=full num representation of a Year in 4 digits
	$dateMonth = ($month != '')?$month:date("m"); // date() returns a formatted date string and m=Numeric representation of a month, with leading zeros
	$date = $dateYear.'-'.$dateMonth.'-01'; //concatenates year, month and adds -01 for first day of any month
	$currentMonthFirstDay = date("N",strtotime($date)); //strtotime turns text date into Unix timestamp, N=ISO 8601 numeric representation of the day of the week
	//cal_days_in_month -> Gets the number of days in a month for a specified year and calendar
	$totalDaysOfMonth = cal_days_in_month(CAL_GREGORIAN,$dateMonth,$dateYear);
	$totalDaysOfMonthDisplay = ($currentMonthFirstDay == 1)?($totalDaysOfMonth):($totalDaysOfMonth + ($currentMonthFirstDay - 1));
	//grab the total number of days so it can looped later to display the calendar days
	$boxDisplay = ($totalDaysOfMonthDisplay <= 35)?35:42;
	//save previous month in a formatted date string
	$prevMonth = date("m", strtotime('-1 month', strtotime($date)));
	//save previous year in a formatted date string
	$prevYear = date("Y", strtotime('-1 month', strtotime($date)));

	$totalDaysOfMonth_Prev = cal_days_in_month(CAL_GREGORIAN, $prevMonth, $prevYear);
?>
	<!-- Main class to contain the entire calendar -->
	<main class="calendar-contain">
		<!-- section that contains the upper and lower bar. It allows section of date and year and display username -->
		<section class="title-bar">
			<div class="title-bar__month">
				<select class="month-dropdown">
					<!-- calls PHP function to get a list of months-->
					<?php echo getMonthList($dateMonth); ?>
				</select>
			</div>
			<!-- debuggin code to display usernmae -->
			<div class="title-bar__year"> Welcome <?php echo  $username; ?>! </div>
			<div class="title-bar__year">
				<select class="year-dropdown">
					<!-- calls PHP function to get a list of years-->
					<?php echo getYearList($dateYear); ?>
				</select>
			</div>
		</section>
		
		<aside class="calendar__sidebar">
			<div id="event_list">
				<?php echo getEvents(); ?>
			</div>
			<!-- button for adding an event. It uses jquery .slideToggle() when .documentReady to hide and display form on click-->
			<a href="#" class="add-event-btn">Добавить</a>
			<!-- button for deleting an event. It uses jquery .slideToggle() when .documentReady to hide and display form on click -->
			<a href="#" class="delete-event-btn">Удалить</a> 
			<!-- button for updating an event. It uses jquery .slideToggle() when .documentReady to hide and display form on click -->
			<a href="#" class="update-event-btn">Обновить</a> 
			<!-- form for adding an event, it gets activated by a jquery function below -->
			<div id="event_add_frm" style="display:none;">
        <!-- Corrected eventAddFrm form -->
<form id="eventAddFrm" action="#">
  <div class="form-group">
    <label>Добавить название события:</label>
    <input type="text" class="form-control" name="event_title" id="event_title" required>
  </div>
  <div class="form-group">
    <label>Дата</label>
    <!-- important to have id="addEvent_date" as this value will be assigned later when we get the events-->
    <input type="text" class="form-control" name="event_date" id="addEvent_date" value="<?php echo date("Y-m-d"); ?>" readonly>
  </div>
  <div class="form-group">
    <label>Добавьте другие имена пользователей для групповых мероприятий.</label>
    <input type="text" class="form-control" name="event_group" id="event_group" required>
  </div>
  <div class="form-group">
    <label>Выберите цвет события:</label>
    <select name="event_color" id="event_color" required>
      <option value="#ff0000">Красный (Важный)</option>
      <option value="#FFFF00">Желтый (Обычный)</option>
      <option value="#008000">Зеленый (Низкий приоритет)</option>
    </select>
  </div>
  <input type="submit" name="event_submit" class="btn btn-default" value="Добавить"> <!-- Added the name attribute -->
</form>
      </div>
			<!-- form for deleting an event, it gets activated by a jquery function below -->
			<div id="event_delete_frm" style="display:none;">
				<form id="eventDeleteFrm" action="#">
					<div class="form-group">
						<label>Удалить название мероприятия:</label>
						<input type="text" class="form-control" name="event_title" id="event_title" required>
					</div>
					<div class="form-group">
						<label>Дата:</label>
						<!-- important to have id="deleteEvent_date" as this value will be assigned later when we get the events-->
						<input type="text" class="form-control" name="event_date" id="deleteEvent_date" value="<?php echo date("Y-m-d"); ?>" readonly>
					</div>
					<input type="submit" name="event_submit" class="btn btn-default" value="Submit">
				</form>
			</div>
			<!-- form for updating an event, it gets activated by a jquery function below -->
			<div id="event_update_frm" style="display:none;">
				<form id="eventUpdateFrm" action="#">
					<div class="form-group">
						<label>Введите название текущего события:</label>
						<input type="text" class="form-control" name="event_title" id="event_title" required>
					</div>
					<div class="form-group">
						<label>Введите название нового события:</label>
						<input type="text" class="form-control" name="new_event_title" id="new_event_title" required>
					</div>
					<div class="form-group">
						<label>Дата:</label>
						<!-- important to have id="updateEvent_date" as this value will be assigned later when we get the events-->
						<input type="text" class="form-control" name="event_date" id="updateEvent_date" value="<?php echo date("Y-m-d"); ?>" readonly>
					</div>
					<div class="form-group">
						<label>Добавьте оригинальные имена других пользователей для групповых мероприятий.</label>
						<input type="text" class="form-control" name="old_event_group" id="old_event_group" required>
					</div>
					<div class="form-group">
						<label>Добавьте новые имена пользователей для групповых мероприятий.</label>
						<input type="text" class="form-control" name="new_event_group" id="new_event_group" required>
					</div>
					<input type="submit" name="event_submit" class="btn btn-default" value="Submit">
				</form>
			</div>
		</aside>
		
		<section class="calendar__days">
			<!-- displays Mon - Sun titles -->
			<section class="calendar__top-bar">
				<span class="top-bar__days">Mon</span>
				<span class="top-bar__days">Tue</span>
				<span class="top-bar__days">Wed</span>
				<span class="top-bar__days">Thu</span>
				<span class="top-bar__days">Fri</span>
				<span class="top-bar__days">Sat</span>
				<span class="top-bar__days">Sun</span>
			</section>
			<?php 
  //ИЗМЕНИЛ ИВЕНТ ТУТ ВСЕ СЛОМАЛОСЬ ---------------------------------------------------------------------------------
  $dayCount = 1;
  $eventNum = 0;
  //class=""calendar__week" for displaying each day of the week
  echo '<section class="calendar__week">';
  //starts cb=1 and loops until cb is less than the total number of days in a month
  for($cb=1;$cb<=$boxDisplay;$cb++){

    if(($cb >= $currentMonthFirstDay || $currentMonthFirstDay == 1) && $cb <= ($totalDaysOfMonthDisplay)){
      // Current date
      $currentDate = $dateYear.'-'.$dateMonth.'-'.$dayCount;
      
      // Get number of events based on the current date and username
      global $db;
      //query to grab events where the username equals the username in session and the data
      $result = $db->query("SELECT title, event_color FROM events WHERE event_date = '".$currentDate."' AND event_status = 1 AND username = '$username' ");
      $group_result = $db->query("SELECT title FROM share WHERE event_date = '".$currentDate."'  AND username = '$username' ");  
      //save number of row in eventNum
      $eventNum = $result->num_rows;
      $eventGroupNum = $group_result->groupnum_rows;  

      $eventTotalNum=$eventNum+$eventGroupNum; 
      
      // Get event color
      $event_color = null;
      if ($eventNum > 0) {
        $row = $result->fetch_assoc();
        $event_color = $row['event_color'];
      }

      // Define date cell color to pink if the date is today's date
      if(strtotime($currentDate) == strtotime(date("Y-m-d"))){
        echo '
          <div class="calendar__day today" onclick="getEvents(\''.$currentDate.'\');" style="background-color: '.$event_color.'">
            <span class="calendar__date">'.$dayCount.'</span>
            <span class="calendar__task calendar__task--today">'.$eventTotalNum.' Events</span>
          </div>
        ';
      }elseif($eventTotalNum > 0){ //checks that number of events is not empty to display blue color and events counts
        echo '
          <div class="calendar__day event" onclick="getEvents(\''.$currentDate.'\');" style="background-color: '.$event_color.'">
            <span class="calendar__date">'.$dayCount.'</span>
            <span class="calendar__task">'.$eventTotalNum.' Events</span>
          </div>
        ';
      }else{ //otherwise display black font and display the event count of 0
        echo '
          <div class="calendar__day no-event" onclick="getEvents(\''.$currentDate.'\');">
            <span class="calendar__date">'.$dayCount.'</span>
            <span class="calendar__task">'.$eventTotalNum.' Events</span>
          </div>
        ';
      }
      $dayCount++;
    }else{
      if($cb < $currentMonthFirstDay){
        $inactiveCalendarDay = ((($totalDaysOfMonth_Prev-$currentMonthFirstDay)+1)+$cb);
        $inactiveLabel = 'expired';
      }else{
        $inactiveCalendarDay = ($cb-$totalDaysOfMonthDisplay);
        $inactiveLabel = 'upcoming';
      }
      echo '
        <div class="calendar__day inactive">
          <span class="calendar__date">'.$inactiveCalendarDay.'</span>
          <span class="calendar__task">'.$inactiveLabel.'</span>
        </div>
      ';
    }
    echo ($cb%7 == 0 && $cb != $boxDisplay)?'</section><section class="calendar__week">':'';
  }
  echo '</section>';
?>
		</section>
		<!-- footer that includes the left arrow, reset password, sign out, and right arrow buttons -->
		
	</main>
	<!-- Javascript functions for getting calendar, events and calendarEvents--> 
	<script>
		//when called uses post to get the calendar based on the year and month provided
		function getCalendar(target_div, year, month){
			$.ajax({
				type:'POST',
				url:'functions.php',
				data:'func=getCalender&year='+year+'&month='+month,
				success:function(html){
					$('#'+target_div).html(html);
				}
			});
		}
		//when called uses post to ge the calendar based on the year and month provided
		function getEvents(date){
			$.ajax({
				type:'POST',
				url:'functions.php',
				data:'func=getEvents&date='+date,
				success:function(html){
					$('#event_list').html(html);
				}
			});
			
			// Add date to event form
			$('#addEvent_date').val(date);
			$('#deleteEvent_date').val(date);
			$('#updateEvent_date').val(date);
			
		}
		//when called uses post to ge the events date based on the year, month, and date provided

		function getCalendarEvents(target_div, year, month, date) {
		getEvents(date); // Call getEvents before making the AJAX request
		$.ajax({
			type: 'POST',
			url: 'functions.php',
			data: 'func=getCalender&year=' + year + '&month=' + month,
			success: function (html) {
			$('#' + target_div).html(html);
			}
		});
		}
		//JQUERY METHODS
		$(document).ready(function(){
			//jquery for month dropdown that triggers anytime there is a change. Please see month-dropdown HTML ID 
			$('.month-dropdown').on('change',function(){
				getCalendar('calendar_div', $('.year-dropdown').val(), $('.month-dropdown').val());
			});
			//jquery for year dropdown that triggers anytime there is a change. Please see year-dropdown HTML ID 
			$('.year-dropdown').on('change',function(){
				getCalendar('calendar_div', $('.year-dropdown').val(), $('.month-dropdown').val());
			});
			//Jquery .slideToggle() to hide and show add form on click. It listens to add-event-btn ID
			$('.add-event-btn').on('click',function(){
				$('#event_add_frm').slideToggle()
				;
			});
			//Jquery .slideToggle() to hide and show add form on click. It listens to delete-event-btn ID
			$('.delete-event-btn').on('click',function(){
				$('#event_delete_frm').slideToggle()
				;
			});
			//Jquery .slideToggle() to hide and show add form on click. It listens to update-event-btn ID
			$('.update-event-btn').on('click',function(){
				$('#event_update_frm').slideToggle()
				;
			});
			//add jquery. Uses POSt to change data  ДОБАВЛЕНИЕ ИВЕНТА
			$('#eventAddFrm').submit(function(event){
				event.preventDefault();
				$(':input[type="submit"]').prop('disabled', true);
				$('#event_add_frm').css('opacity', '0.5');
				$.ajax({
					type:'POST',
					url:'functions.php',
					data:$('#eventAddFrm').serialize()+'&func=addEvent',
					success:function(status){
						console.log(`status: ${status}`);
						if(status == 1){
							$('#event_title').val('');
							swal("Success!", "Event added successfully.", "success");
						}else{
							swal("Failed!", "Something went wrong, please try again.", "error");
						}
						$(':input[type="submit"]').prop('disabled', false);
						$('#event_add_frm').css('opacity', '');
						
						var date = $('#addEvent_date').val();
						var dateSplit = date.split("-");
						getCalendarEvents('calendar_div', dateSplit[0], dateSplit[1], date);
					}
				});
			});

			//incorporating delete jquery. Uses POSt to change data 
			$('#eventDeleteFrm').submit(function(event){
				event.preventDefault();
				$(':input[type="submit"]').prop('disabled', true);
				$('#event_delete_frm').css('opacity', '0.5');
				$.ajax({
					type:'POST',
					url:'functions.php',
					data:$('#eventDeleteFrm').serialize()+'&func=deleteEvent',
					success:function(status, insert, numOfRows){
						console.log(`status: ${status}`);
						console.log(`insert: ${insert}`);
						console.log(`numOfRows: ${numOfRows}`);
						if(status == 1){
							$('#event_title').val('');
							swal("Success!", "Event deleted successfully.", "success");
						}else{
							swal("Failed!", "Something went wrong, please try again.", "error");
						}
						$(':input[type="submit"]').prop('disabled', false);
						$('#event_delete_frm').css('opacity', '');
						
						var date = $('#deleteEvent_date').val();
						var dateSplit = date.split("-");
						getCalendarEvents('calendar_div', dateSplit[0], dateSplit[1], date);
					}
				});
			});

			//incorporating update jquery. Uses POSt to change data 
			$('#eventUpdateFrm').submit(function(event){
				event.preventDefault();
				$(':input[type="submit"]').prop('disabled', true);
				$('#event_update_frm').css('opacity', '0.5');
				$.ajax({
					type:'POST',
					url:'functions.php',
					data:$('#eventUpdateFrm').serialize()+'&func=updateEvent',
					success:function(status){
						console.log(`status: ${status}`);
						if(status == 1){
							$('#event_title').val('');
							$('#new_event_title').val('');
							swal("Success!", "Event updated successfully.", "success");
						}else{
							swal("Failed!", "Something went wrong with the udpate, please try again.", "error");
						}
						$(':input[type="submit"]').prop('disabled', false);
						$('#event_update_frm').css('opacity', '');
						
						var date = $('#updateEvent_date').val();
						var dateSplit = date.split("-");
						getCalendarEvents('calendar_div', dateSplit[0], dateSplit[1], date);
					}
				});
			});
		});
	</script>
	<script type="text/javascript">
  $(document).ready(function(){
    // Delete event
    $('.delete-event').click(function(){
      var event_id = $(this).attr('data-event-id');
      $.ajax({
        type: 'POST',
        url: 'functions.php',
        data: 'func=deleteEvent&event_id=' + event_id,
        success: function(status){
          if(status == 1){
            $('#event_'+event_id).remove();
          }else{
            alert('Error deleting event. Please try again.');
          }
        }
      });
    });

    // Edit event
    $('.edit-event').click(function(){
      var event_id = $(this).attr('data-event-id');
      // Get event data from database
      $.ajax({
        type: 'POST',
        url: 'functions.php',
        data: 'func=getEvent&event_id=' + event_id,
        success: function(event_data){
          // Fill event data into edit form
          $('#event_update_id').val(event_data.id);
          $('#event_update_title').val(event_data.title);
          $('#event_update_date').val(event_data.event_date);
          $('#event_update_group').val(event_data.event_group);
          $('#event_update_color').val(event_data.event_color);
          // Show edit form
          $('#event_update_frm').slideToggle();
        }
      });
    });
  });
</script>
<?php
}

//Generate months options list for select box
function getMonthList($selected = ''){
	$options = '';
	for($i=1;$i<=12;$i++)
	{
		$value = ($i < 10)?'0'.$i:$i;
		$selectedOpt = ($value == $selected)?'selected':'';
		$options .= '<option value="'.$value.'" '.$selectedOpt.' >'.date("F", mktime(0, 0, 0, $i+1, 0, 0)).'</option>';
	}
	return $options;
}

//Generate years options list for select box
function getYearList($selected = ''){
	$yearInit = !empty($selected)?$selected:date("Y");
	$yearPrev = ($yearInit - 5);
	$yearNext = ($yearInit + 5);
	$options = '';
	for($i=$yearPrev;$i<=$yearNext;$i++){
		$selectedOpt = ($i == $selected)?'selected':'';
		$options .= '<option value="'.$i.'" '.$selectedOpt.' >'.$i.'</option>';
	}
	return $options;
}

//Generate events list in HTML format
function getEvents($date = ''){
	$_username = htmlspecialchars($_SESSION["username"]);
	$date = $date?$date:date("Y-m-d");
	
	$eventListHTML = '<h2 class="sidebar__heading">'.date("l", strtotime($date)).'<br>'.date("F d", strtotime($date)).'</h2>';
	$event_groupListHTML ='';
	 
	// Fetch events based on the specific date
	global $db;
	$result = $db->query("SELECT title FROM events WHERE event_date = '".$date."' AND event_status = 1 AND username = '$_username' ");
	$group_result = $db->query("SELECT title FROM share WHERE event_date = '".$date."' AND username = '$_username' ");
	if($result->num_rows > 0){
		$eventListHTML .= '<ul class="sidebar__list">';
		$eventListHTML .= '<li class="sidebar__list-item sidebar__list-item--complete">Events</li>';
		$i=0;
		while($row = $result->fetch_assoc()){ $i++;
            $eventListHTML .= '<li class="sidebar__list-item"><span class="list-item__time">'.$i.'.</span>'.$row['title'].'</li>';
        }
		$eventListHTML .= '</ul>';
	}
	if($group_result->groupnum_rows > 0){
		$event_groupListHTML .= '<ul class="sidebar__list">';
		$event_groupListHTML .= '<li class="sidebar__list-item sidebar__list-item--complete">Group Events:</li>';
		$i=0;
		while($row = $group_result->fetch_assoc()){ $i++;
            $event_groupListHTML .= '<li class="sidebar__list-item"><span class="list-item__time">'.$i.'.</span>'.$row['title'].'</li>';
        }
		$event_groupListHTML .= '</ul>';
	}
	echo $eventListHTML;
	echo $event_groupListHTML;

}

//Insert events in the database 
function addEvent($postData) {
	$_username = htmlspecialchars($_SESSION["username"]);
	$status = 0;
  
	if (!empty($postData['event_title']) && !empty($postData['event_date'])) {
	  global $db;
  
	  $event_title = $db->real_escape_string($postData['event_title']);
	  $event_color = $db->real_escape_string($postData['event_color']); // Get the selected color
	  $insert = $db->query("INSERT INTO events (title, event_date, username, event_color) VALUES ('" . $event_title . "', '" . $postData['event_date'] . "', '" . $_username . "', '" . $event_color . "')");
	  if ($insert) {
		$status = 1;
	  }
	}
  
	echo $status;
  }


//delete events from the database
function deleteEvent($postData){
	$_username = htmlspecialchars($_SESSION["username"]);
	$status = 0;

	if(!empty($postData['event_title']) && !empty($postData['event_date'])){
		global $db;
		$event_title = $db->real_escape_string($postData['event_title']);
		$new_group = $db->real_escape_string($postData['event_group']);
		//makes sure that only the username in session is able to delete their own events and not others. 
		$insert = $db->query("DELETE FROM events WHERE title='".$event_title."' AND username = '$_username' ");
		$insert .= $db->query("DELETE FROM share WHERE title='".$event_title."' AND username = '$new_group' ");
		if($insert) {
			$status = 1;
		}
	}
	
	echo $status;
}
//DELETE FROM events WHERE title = 'LauraVisitsMiami' AND username = 'Laura';

//update events on the database
function updateEvent($postData){
	$_username = htmlspecialchars($_SESSION["username"]);
	$status = 0;
	$insert = '';
	if(!empty($postData['event_title']) && !empty($postData['new_event_title']) && !empty($postData['event_date'])){
		global $db;
		$event_title = $db->real_escape_string($postData['event_title']);
		$new_event_title = $db->real_escape_string($postData['new_event_title']);
		$old_group = $db->real_escape_string($postData['old_event_group']);
		$new_group = $db->real_escape_string($postData['new_event_group']);
		//makes sure that only the username in session is able to delete their own events and not others. 
		$insert = $db->query("UPDATE events SET title='".$new_event_title."' WHERE title='".$event_title."' AND username = '$_username' ");
		$insert .= $db->query("UPDATE share SET title='".$new_event_title."', username='".$new_group."' WHERE title='".$event_title."' AND username='".$old_group."'");
		if($insert){
			$status = 1;
		}
	}
	
	echo $status;
}
<div class="wrap">
		<div>
			<a class="wc-update-now button-primary" id="delete_log">
				Delete logs
			</a>
		</div>
		<br>
      <?php

	
	 if(!empty($woocommerce_bookings_google_calendar_sync_logs)){
		 foreach ($woocommerce_bookings_google_calendar_sync_logs as $key => $value){
			// echo "<pre>";
				// print_r(json_encode($value['log']));
			// echo "</pre>";
	  ?>
      <div class="log-data">
         <a class="collapse" href="javascript:void(0);">
         <span class="dashicons dashicons-arrow-right-alt2"></span>
         <span class="log-title-text">
         Google To Woo Log [ <?php print_r($value['Event_Title']);?> ]
         </span>
         <span class="log-title-date"> 
         <?php print_r($value['currenttime_date']); ?>
         </span>
         </a>
         <div class='hidden grid-div'>
           <table class='gridtable'>
			<tbody>
               <tr>
                  <td> 
						<?php echo '<pre>'; print_r($value['log']); echo '</pre>'; ?>
                  </td>
                  <td>-</td>
                  <td class='center'>
                    <span class="dashicons dashicons-no" ></span>
                  </td>
                  <td></td>
               </tr>
		   </tbody>
		</table>
               <div class="hidden grid-div">
                  <?php echo __('No entries found') ; ?>
				       </div>
					   </div>
					      <!-- log data -->
				</div>
		 <?php }} ?>
				  
              
       
         
      
</div>
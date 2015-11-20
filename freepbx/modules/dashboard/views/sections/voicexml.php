<div class="container-fluid">
	<div class='row'>
		<div class='col-sm-12 less-padding'>
			<div class="box">
				<p class='text-center'><strong><?php echo _("Sessions Status")?></strong></p>
				<div class='row'>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Opened: ")?><em><?php echo _($statistics['opened'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Peak: ")?><em><?php echo _($statistics['peak'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Error: ")?><em><?php echo ($statistics['error'] > 0) ? _("<span style='color: red'>".$statistics['error']."</span>") : _($statistics['error'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Denied: ")?><em><?php echo ($statistics['denied'] > 0) ? _("<span style='color: red'>".$statistics['denied']."</span>") : _($statistics['denied'])?></em></small></p>
					</div>
				</div>
				<div class='row'>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Refused: ")?><em><?php echo ($statistics['refused'] > 0) ? _("<span style='color: red'>".$statistics['refused']."</span>") : _($statistics['refused'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Waited: ")?><em><?php echo _($statistics['waited'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Needed: ")?><em><?php echo _($statistics['needed'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Max Duration: ")?><em><?php echo _($statistics['maxduration'])?></em></small></p>
					</div>																							
				</div>
			</div>
		</div>
	</div>
	<?php if ($_SESSION['AMP_user']->username == "admin") {?>
	<div class='row'>
		<div class='col-sm-12 less-padding'>
			<div class="box">
				<p class='text-center'><strong><?php echo _("Actions Counters")?></strong></p>
				<div class='row'>
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Prompts: ")?><em><?php echo _($statistics['prompts'])?></em></small></p>
					</div>				
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Transfers: ")?><em><?php echo _($statistics['transfers'])?></em></small></p>
					</div>
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Transfers alternative: ")?><em><?php echo _($statistics['transfersAlternative'])?></em></small></p>
					</div>					
					
				</div>
				<div class='row'>				
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Recognizes: ")?><em><?php echo _($statistics['recognizes'])?></em></small></p>
					</div>
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Speechs: ")?><em><?php echo _($statistics['speechs'])?></em></small></p>
					</div>					
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Speechs error: ")?><em><?php echo ($statistics['speechsError'] > 0) ? _("<span style='color: red'>".$statistics['speechsError']."</span>") : _($statistics['speechsError'])?></em></small></p>
					</div>
				</div>
				<div class='row'>	
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Records: ")?><em><?php echo _($statistics['records'])?></em></small></p>
					</div>					
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Originates: ")?><em><?php echo _($statistics['originates'])?></em></small></p>
					</div>					
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Originates error: ")?><em><?php echo ($statistics['originatesError'] > 0) ? _("<span style='color: red'>".$statistics['originatesError']."</span>") : _($statistics['originatesError'])?></em></small></p>
					</div>
				</div>
				<div class='row'>
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Connections lost: ")?><em><?php echo ($statistics['connectionsLost'] > 0) ? _("<span style='color: red'>".$statistics['connectionsLost']."</span>") : _($statistics['connectionsLost'])?></em></small></p>
					</div>					
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Connections retrieve: ")?><em><?php echo _($statistics['connectionsRetrieve'])?></em></small></p>
					</div>
					<div class='col-sm-4'>
						<p class='text-center'><small><?php echo _("Connections error: ")?><em><?php echo ($statistics['connectionsError'] > 0) ? _("<span style='color: red'>".$statistics['connectionsError']."</span>") : _($statistics['connectionsError'])?></em></small></p>
					</div>
				</div>				
			</div>
		</div>
	</div>
	<?php }?>
	<div class='row'>
		<div class='col-sm-12 less-padding'>
			<div class="box">
				<p class='text-center'><strong><?php echo _("Average Counters")?></strong></p>
				<div class='row'>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Sessions: ")?><em><?php echo _($statistics['avgSessions'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Duration: ")?><em><?php echo _($statistics['avgDuration'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Response: ")?><em><?php echo _($statistics['avgResponse'])?></em></small></p>
					</div>					
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("CAPS: ")?><em><?php echo _($statistics['avgCAPS'])?></em></small></p>
					</div>
				</div>
				<div class='row'>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Speech: ")?><em><?php echo _($statistics['avgSpeech'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Score: ")?><em><?php echo _($statistics['avgScore'])?></em></small></p>
					</div>
					<div class='col-sm-3'>
						<p class='text-center'><small><?php echo _("Transfer: ")?><em><?php echo _($statistics['avgTransfer'])?></em></small></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
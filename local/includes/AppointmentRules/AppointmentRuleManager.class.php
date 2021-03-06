<?php
$loader->requireOnce('includes/AppointmentRules/AppointmentRulesetValidator.class.php');
class AppointmentRuleManager {

	var $rulesets = array();
	var $messages = array();
	var $override = true;

	function isValid($appointment) {
		$this->populateRules($appointment);

		$status = true;
		$overrides = array();
		foreach($this->rulesets as $ruleset) {
			$s = $ruleset->isValid($appointment);
			if (!$s) {
				$status = false;
				$this->messages[] = $ruleset->getMessage();
				$overrides[] = $ruleset->canOverride();
			}
			//$this->messages[] = $ruleset->getMessage(); // enable for debug
		}
		if(in_array(false,$overrides)) {
			$this->override = false;
		}

		return $status;
	}

	function populateRules($appointment) {
		$pid = EnforceType::int($appointment->get('provider_id'));
		$cid = EnforceType::int($appointment->get('reason'));
		$rid = EnforceType::int($appointment->get('room_id'));

		$sql = "select * from appointment_ruleset where enabled = 1 and
		(provider_id = 0 or provider_id = $pid) and (procedure_id = 0 or procedure_id = $cid) and (room_id = 0 or room_id = $rid)"; 
	
		$db = new clniDb();
		$res = $db->execute($sql);
		
		while($res && !$res->EOF) {
			$id = $res->fields['appointment_ruleset_id'];
			$this->rulesets[$id] = new AppointmentRulesetValidator($id);
			$res->moveNext();
		}

	}

	function getMessage() {
		return implode('<br>',$this->messages);
	}
}
?>

<?php
$loader->requireOnce('includes/AppointmentRules/AppointmentRuleset.class.php');
class AppointmentRuleManager {

	var $rulesets = array();
	var $messages = array();


	function isValid($appointment) {
		$this->populateRules($appointment);

		$status = true;
		foreach($this->rulesets as $ruleset) {
			$s = $ruleset->isValid($appointment);
			if (!$s) {
				$status = false;
				$this->messages[] = $ruleset->getMessage();
			}
		}

		return $status;
	}

	function populateRules($appointment) {
		$pid = $appointment->get('provider_id');
		$cid = $appointment->get('reason');
		$rid = $appointment->get('room_id');

		$sql = "select * from appointment_ruleset where 
				(provider_id = 0 or provider_id = $pid) and (procedure_id = 0 or procedure_id = $cid) and (room_id = 0 or room_id = $rid)";
	
		$db = new clniDb();
		$res = $db->execute($sql);
		while($res && !$res->EOF) {
			$id = $res->fields['appointment_ruleset_id'];
			$this->rulesets[$id] = new AppointmentRuleset($id);
			$res->moveNext();
		}
	}

	function getMessage() {
		return implode('<br>',$this->messages);
	}
}
?>
<?php
/**
 * Object Relational Persistence Mapping Class for table: Patient
 *
 * @package	com.clear-health.clearhealth
 * @author	Joshua Eichorn <jeichorn@mail.com>
 */

/**#@+
 * Required Libs
 */
$loader->requireOnce('ordo/MergeDecorator.class.php');
/**#@-*/

/**
 * Object Relational Persistence Mapping Class for table: Patient
 *
 * @package	com.clear-health.clearhealth
 * @todo: add release_of_information_code
 */
class Patient extends MergeDecorator {

	/**#@+
	 * Fields of table: Patient mapped to class members
	 */
	var $person_id = "";
	/**#@-*/

	/**
	 * The base Person instance that this patient is extending
	 */
	var $person;
	var $record_number = "";
	var $employer_name = "";
	var $default_provider = "";
	var $confidentiality = "";
	var $specialNeedsNote = '';
	var $specialNeedsTranslator = '';

	var $_table = 'patient';
	var $_key = 'person_id';
	var $_internalName='Patient';

	var $storage_metadata =  array('int' => array(), 'date' => array('signed_hipaa'=>''), 'string' => array(), 'text' => array());
	
	
	/**
	 * Setup some basic attributes
	 * Shouldn't be called directly by the user, user the factory method on ORDataObject
	 */
	function Patient($db = null) {
		$this->ORDataObject($db);
		$this->merge('person',ORDataObject::factory('Person'));
		
	}

	/**
	 * Called by factory with passed in parameters, you can specify the primary_key of Patient with this
	 */
	function setup($id = 0) {
		$this->person->set('id',$id);
		$this->set('id',$id);
		if ($id > 0) {
			$this->populate();
		}
	}

	/**
	 * Generate new record number, currently uses database sequence
	 */
	function generate_record_number() {
		
		$rn = $this->_db->GenID("record_sequence");
		while(!$this->isRecordNumUnique($rn)) {
			$rn = $this->_db->GenID("record_sequence");
		}
		return $rn;
	}

	function isRecordNumUnique($value = false) {
		if ($value !== false) {
			$rn = $this->dbHelper->quote($value);
		}
		else {
			$rn = $this->dbHelper->quote($this->get('record_number'));
		}
		$sql = "select person_id from patient where record_number = $rn";
		if ($this->dbHelper->getOne($sql)) {
			return false;
		}
		return true;
	}


	/**
	 * Persist the data
	 */
	function persist() {
		if (strlen($this->get("record_number")) == 0) {
			$this->record_number = $this->generate_record_number();	
		}
		$ret = $this->mergePersist('person_id');
		if ($this->get('id') == 0) {
			$this->set('id',$this->person->get('id'));
		}
		if (parent::persist()) {
			return true;
		}
		return $ret;
	}

	/**
	 * Load the data from the db
	 */
	function populate() {
		parent::populate('person_id');
		$this->mergePopulate('person_id');
	}

	/**#@+
	 * Getters and Setters for Table: Patient
	 */


	/**
	 * Setter for Primary Key: person_id
	 * This are just here for bc, some stuff was calling them directly
	 */
	function get_person_id() {
		return $this->person_id;
	}
	function set_person_id($id)  {
		$this->person_id = $id;
	}

	function get_search_name() {
		if ($this->get('id') > 0) {
			return $this->get('last_name').", ".$this->get('first_name')." #".$this->get('record_number');
		}
	}


	/**
	 * Setup employer relationship
	 */
	function set_employer($branch_id) {
		if ($branch_id > 0) {
			if ($this->id > 0) {
				$this->person->relate($branch_id,0);
			}
		}
	}

	function get_employer() {
		if ($this->id > 0) {
			$res = $this->_execute("select c.company_id from company c inner join person_company using(company_id) where person_id = $this->id");
			return $res->fields['company_id'];
		}
		return 0;
	}

	/**#@-*/

	/**#@+
	 * Proxy methods to the person class were decorating
	 */

	function getGenderList() {
		return $this->person->getGenderList();
	}
	function getTypeList() {
		return $this->person->getTypeList();
	}
	function getIdentifierTypeList() {
		return $this->person->getIdentifierTypeList();
	}
	function get_numbers() {
		return $this->person->get_numbers();
	}
	function get_addresses() {
		return $this->person->get_addresses();
	}
	function peopleByCompany($company_id,$type) {
		return $this->person->peopleByCompany($company_id,$type);
	}
	function &numberByType($type,$value = false) {
		$return =& $this->person->numberByType($type,$value);
		return $return;
	}

	function get_guarantor() {
		$pp =& Celini::newOrdo('PersonPerson',array(0,$this->get('id')));
		return $pp->get('guarantorPerson');
	}
	
	function get_guarantees() {
		$db =& Celini::dbInstance();
		$sql = "SELECT person_id FROM person_person WHERE guarantor = 1 AND related_person_id = ".$db->quote($this->get('id'));
		$res = $db->execute($sql);
		$guarantees = array();
		for($res->MoveFirst();!$res->EOF;$res->MoveNext()) {
			$guarantees[] = $res->fields['person_id'];
		}
		return $guarantees;
	}
	
	function value_age() {
		return $this->person->value('age');
	}
	
	function valueList_related_people() {
		$db =& Celini::dbInstance();
		$sql = "SELECT p.person_id,related_person_id,CONCAT(p.last_name,', ',p.first_name) pname, CONCAT(rp.last_name,', ',rp.first_name) rpname FROM person_person LEFT JOIN person p ON person_person.person_id=p.person_id LEFT JOIN person rp ON person_person.related_person_id=rp.person_id WHERE person_person.person_id=".$db->quote($this->get('person_id'))." OR person_person.related_person_id=".$db->quote($this->get('person_id'));
		$res = $db->execute($sql);
		$related = array();
		while($res && !$res->EOF) {
			if($res->fields['person_id']==$this->get('id')) {
				$related[$res->fields['related_person_id']]=$res->fields['rpname'];
			} else {
				$related[$res->fields['person_id']]=$res->fields['pname'];
			}
			$res->MoveNext();
		}
		return $related;
	}
	
	/**
	 * An alias to {@link Person::numberValueByType()}.
	 *
	 * @param string
	 * @return string
	 */
	function numberValueByType($type) {
		$return = $this->person->numberValueByType($type);
		return $return;
	}
	
	
	function &address() {
		$return =& $this->person->address();
		return $return;
	}
	function &nameHistoryList() {
		$return =& $this->person->nameHistoryList();
		return $return;
	}
	function &identifierList() {
		$return =& $this->person->identifierList();
		return $return;
	}
	function &insuredRelationshipList() {
		$return =& $this->person->insuredRelationshipList();
		return $return;
	}
	function lookupType($id) {
		return $this->person->lookupType($id);
	}
	function getMaritalStatusList() {
		return $this->person->getMaritalStatusList();
	}
	/**#@-*/

	/**
	 * Return a datasource of all patients
	 *
	 */
	function &patientList($company_id = 0) {
		settype($company_id,'int');
		$ds =& new Datasource_sql();
		$sql = array(
			'cols' 	=> "p.person_id, concat_ws(' ',first_name,last_name) name, n.number phone, c.number cell, email, 'link' link ",
			'from' 	=> "$this->_table p inner join person e using(person_id) left join person_company pc using(person_id)
					left join person_address pa using(person_id) left join address a using(address_id)
					left join person_number pn on p.person_id = pn.person_id
					left join number n on pn.number_id = n.number_id and n.number_type = 1
					left join number c on pn.number_id = c.number_id and c.number_type = 2
					",
			'groupby' => 'person_id',
			'orderby' => 'name'
			);
		$cols = array('name' => 'Name','phone' => 'Phone');

		if ($company_id > 0) {
			$sql['where'] = "pc.company_id = $company_id";
		}
		$ds->setup($this->_db,$sql,$cols);
		return $ds;
	}

	function toArray() {
		$ret = $this->person->toArray();
		$ret['record_number'] = $this->get('record_number');
		return $ret;
	}

	function get_print_default_provider() {
		$u =& User::fromId($this->get('default_provider'));
		return $u->get('username');	
	}

	function get_print_registration_location() {
		$ps =& ORDataObject::Factory('PatientStatistics',$this->get('id'));

		$b =& ORDataObject::Factory('Building');
		$list = $b->getBuildingList();

		$loc = $ps->get('registration_location');
		if (isset($list[$loc])) {
			return $list[$loc];
		}
	}

	function get_defaultPractice() {
		$ps =& ORDataObject::Factory('PatientStatistics',$this->get('id'));
		$loc = $ps->get('registration_location');

		if (empty($loc)) {
			return Celini::newOrdo('Practice',$_SESSION['defaultpractice']);
		}

		$building =& Celini::newOrdo('Building',$loc);
		return Celini::newOrdo('Practice',$building->get('practice_id'));
	}

	function &get_defaultProviderPerson() {
		$user =& Celini::newOrdo('User',$this->get('default_provider'),'ById');
		$pro =& Celini::newOrdo('Provider',$user->get('person_id'));
		if (!$pro->ispopulated()) {
			$db = new clniDb();
			$id = $db->getOne("select person_id from provider");
			$pro =& Celini::newOrdo('Provider',$id);
		}
		return $pro;
	}

	function value_patient() {
		$name = $this->person->value('name');
		if (!empty($name)) {
			$name .= ' #'.$this->get('record_number');
	}
		return $name;
	}

	function value_phone() {
		$n = $this->person->numberByType('Home');
		return $n->get('number');
	}

	function value_balance() {
		return '$'.number_format($this->getBalance());
	}

	function getBalance() {
		$status = $this->accountStatus($this->get('id'));
		return $status['total_balance'];
	}

	function accountStatus($patient_id,$encounter_id = false) {
		$status = array();
		$sql = '
			SELECT
				SUM(IFNULL(total_billed,0)) AS total_billed,
				SUM(IFNULL(total_paid,0)) AS total_paid,
				SUM(IFNULL(writeoffs.writeoff,0)) AS total_writeoff,
				SUM(IFNULL(total_billed,0)) - (SUM(IFNULL(total_paid,0)) 
					+ SUM(IFNULL(writeoffs.writeoff,0))) AS total_balance
			FROM
				encounter AS e
				LEFT JOIN clearhealth_claim AS cc on e.encounter_id = cc.encounter_id
				LEFT JOIN (
					SELECT
						foreign_id,
						SUM(ifnull(writeoff,0)) AS writeoff
					FROM
						payment p
						inner join clearhealth_claim cc on p.foreign_id = cc.claim_id
						inner join encounter e on cc.encounter_id = e.encounter_id
					WHERE
						p.encounter_id = 0 and e.patient_id = '.$this->dbHelper->quote($patient_id).'
					GROUP BY
						foreign_id
				) AS writeoffs ON(writeoffs.foreign_id = cc.claim_id)
			WHERE 
				patient_id = ' . $this->dbHelper->quote($patient_id);

		if ($encounter_id) {
			$sql .= " and e.encounter_id = ".(int)$encounter_id;
		}
		
		$res = $this->_execute($sql);
		if ($res && !$res->EOF) {
			$status['total_billed'] = $res->fields['total_billed'];
			$status['total_paid'] = $res->fields['total_paid'];
			$status['total_writeoff'] = $res->fields['total_writeoff'];
			$status['total_balance'] = $res->fields['total_balance'];
		}
		return $status;
	}

	
	function &getProvider() {
		$prov =& Celini::newORDO('Provider',$this->get('default_provider'));
		return $prov;
	}

	function inDuplicateQueue() {
		$sql = "select count(*) from duplicate_queue where child_id = ".$this->get('id');
		return $this->dbHelper->getOne($sql);
	}

	function value_duplicate_person() {
		$sql = "select parent_id from duplicate_queue where child_id = ".$this->get('id');
		$id = $this->dbHelper->getOne($sql);
		$patient =& Celini::newOrdo('Patient',$id);
		return $patient->value('name').' #'.$patient->value('record_number');
	}
	function get_patientPictureUrl($patientId = "") {
		if ($patientId == "") $patientId = $this->get('person_id');
		$d = Document::FirstDocumentByCategoryName($patientId,"Picture");
                if (is_object($d)) {
                        $pictureTag = Celini::link("thumb","Thumbnail") . 'src=/' . $patientId . "/" . $d->get("name") . '&w=100';
                        return $pictureTag;
                }
		else {
                        $pictureTag = Celini::link("thumb","Thumbnail") . 'src=/blank_person.png&w=100';
			return $pictureTag;

		}
	}

}
?>

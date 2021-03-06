<?php

$loader->requireOnce('/includes/Datasource_sql.class.php');

class Patient_NoteList_DS extends Datasource_sql
{
	/**
	 * Stores the case-sensative class name for this ds and should be considered
	 * read-only.
	 *
	 * This is being used so that the internal name matches the filesystem
	 * name.  Once BC for PHP 4 is no longer required, this can be dropped in
	 * favor of using get_class($ds) where ever this property is referenced.
	 *
	 * @var string
	 */
	var $_internalName = 'Patient_NoteList_DS';
	
	
	/**
	 * The default output type for this datasource.
	 *
	 * This can be overridden by a grid with {@link cGrid::setOutputType()}
	 *
	 * @var string
	 */
	var $_type = 'html';
	var $patientId = '';	
	
	function Patient_NoteList_DS($patient_id) {
		settype($patient_id, 'integer');
		$this->patientId = $patient_id;

		$labels = array(
			'deprecated' => 'Done',
			'priority'   => 'P',
			'note_date'  => 'Date',
			'username'   => 'User',
			'reason'       => 'Reason',
			'note'       => 'Note');
		$this->setTypeDependentLabel('html', 'deprecated', 'Done');

		$this->setup(Celini::dbInstance(),
			array(
				'cols' 	=> "
					priority,
					DATE_FORMAT(note_date, '%m/%d/%Y %H:%i:%s') AS note_date,
					note,
					username,
					patient_note_id,
					if(deprecated,'Yes','No') deprecated,
					reason",
						
				'from' 	=> "patient_note n left join user u on u.user_id = n.user_id",
				'where' => " patient_id = $patient_id",
				'orderby' => "deprecated ASC",
			),
			$labels
		);

		$this->addOrderRule('priority',  'DESC', 0);
		$this->addOrderRule('note_date', 'DESC', 1);

		$this->registerFilter('note_date',array($this, '_addEditLink'),false,"html");
		$this->registerFilter('note',     array($this, 'multiLineFilter'));
		$this->registerFilter('priority', array($this, 'colorLineFilter'), false, 'html');
		$this->template['deprecated'] = "<a href='".Celini::managerLink('depnote',$patient_id)."pnote_id={\$patient_note_id}&current={\$deprecated}&process=true'>{\$deprecated}</a>";
		$this->registerFilter('reason',array($this, '_lookup'));
	}
	function _lookup($value) {
                $em =& Celini::enumManagerInstance();
                return $em->lookup('patient_note_reason', $value);
        }
	function multiLineFilter($content) {
		if (strstr($content,"\n")) {
			$pos = strpos($content,"\n");
			$line1 = trim(substr($content,0,$pos));
			$rest = trim(substr($content,($pos+1)));
			return "<pre><span style='border-bottom: dotted 1px blue;' onmouseover=\"this.parentNode.getElementsByTagName('div').item(0).style.display = 'block'; this.style.borderBottom = '';\">$line1</span><div style='display:none;'>$rest</div></pre>";
		}
		return $content;
	}

	function colorLineFilter($content) {
		switch($content) {
			case 5:
				$color = "red";
				break;
			case 4: 
				$color = "yellow";
				break;
			default:
				$color = "transparent";
				break;
		}

		return "<div style='background-color: $color; font-weight: bold; margin-left: -5px; text-align:  center;'>$content</div>";
	}
	
	function _addEditLink($value, $rowValues) {
		$url = Celini::link(true, true, true, $this->patientId) . 'note_id=' . $rowValues['patient_note_id'];
		return '<a href="' . $url . '">' . $value . '</a>';
	}
}

?>

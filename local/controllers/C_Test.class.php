<?php
$loader->requireOnce('datasources/FeeSchedule_DS.class.php');
$loader->requireOnce('includes/clni/clniActiveGrid.class.php');

$loader->requireOnce('includes/transaction/TransactionManager.class.php');


class C_Test extends Controller {

	function actionScroll() {
		exit;
		$ajax =& Celini::ajaxInstance();
		$ajax->jsLibraries[] = array('scriptaculous');
		$ajax->jsLibraries[] = array('scrollbar','clniGrid');
		$ajax->stubs[] = 'ActiveFeeSchedule';


		$ds =& new FeeSchedule_DS();
		$this->assign('dsSetup',$ds->setupJs('dsSetup'));
		
		
		return $this->view->render('scroll.html');
	}

	function actionGrid() {
		exit;
		$ds =& new FeeSchedule_DS();
		$grid =& new clniActiveGrid('testGrid',$ds);
		$grid->gridWidth = "600px";

		$grid->stubName = 'ActiveFeeSchedule';

		return $grid->render();
	}

	function actionRaw() {
		exit;
		$person =& Celini::newOrdo('Patient',1110);
		var_dump($person->get('gender'));
		$person->set('gender',new ClniValueRaw('gender + 1'));
		$person->persist();
		var_dump($person->get('gender'));
	}

	function actionTrans() {
		exit;
		$tm = new TransactionManager();

		$trans = $tm->createTransaction('Claim');

		$trans->setClaim('206530-4290-201442');
		$trans->setPayer('CHDP','CHDP');

		$trans->type = 'credit';
		$trans->amount = 13.00;
		$trans->paymentDate = date('Y-m-d');

		$tm->processTransaction($trans);
	}

	function actionRadio() {
		exit;
		return $this->view->render('radio.html');
	}
	
	function actionRelationship(){
		exit;
		$practice=&Celini::newORDO('Practice',600001);
		$room=& Celini::newORDO('Room',600010);
		$practice->setParent($room);
		$rooms=&$practice->getParents('Room');
		
		var_dump($rooms->count());
		while($room=&$rooms->current() && $rooms->valid()){
			var_dump($room->get('id'));
			$rooms->next();
		}
	}
	
	function actionTest(){
		exit;
		$prov=&Celini::newORDO('Provider',600011);
		var_dump($prov->get('id'));
		var_dump($prov->isPopulated());
//		var_dump($prov);
		$sched=&Celini::newORDO('Schedule',600392);
		var_dump($sched->getParent('Provider'));
	}
}
?>

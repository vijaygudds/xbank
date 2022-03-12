<?php

class page_reports_loan_bikelegal_bikescasedetailreport extends Page {
	public $title="Bike In Stock Report";
	
	function init(){
		parent::init();

		$form= $this->add('Form');
		$dealer_field=$form->addField('dropdown','dealer')->setEmptyText('All');
		$dealer_field->setModel('ActiveDealer');

		$form->addField('DatePicker','from_date');
		$form->addField('DatePicker','to_date');

		$form->addField('DropDown','legal_status')
				->setEmptyText('All')->setValuelist(['is_in_legal'=>'Is In Legal','is_in_arbitration'=>'Is In Arbitration','is_in_arbitration_process'=>'Is In Atrbitration Process', 'is_in_legal_process'=>'Is In Legal Process']);

		$form->addField('DropDown','account_status')->setEmptyText('All')
									->setValuelist(['Active'=>'Active','InActive'=>'InActive']);

		$form->addField('dropdown','loan_type')->setValueList(array('all'=>'All','vl'=>'VL','pl'=>'PL','fvl'=>'FVL','sl'=>'SL','hl'=>'HL','other'=>'Other'));
		$form->addField('DropDown','last_hearing_stage')->setValuelist(array_combine(LEGAL_CASE_STAGES, LEGAL_CASE_STAGES))->setEmptyText('Any');

		$document=$this->add('Model_Document');
		$document->addCondition('LoanAccount',true);
		foreach ($document as $junk) {
			$form->addField('CheckBox','doc_'.$document->id, $document['name']);
		}

		$form->addSubmit('Get List');

		$account_model = $this->add('Model_Account_Loan');
		$account_model->addCondition('DefaultAC',false);

		$member_j = $account_model->join('members','member_id');
		$member_j->addField('FatherName');
		$member_j->addField('PermanentAddress');
		$member_j->addField('PhoneNos');
		$member_j->addField('landmark');
		$member_j->addField('tehsil');
		$member_j->addField('district');

		$account_model->addExpression('member_sm_account')->set(function($m,$q){
			return  $this->add('Model_Account_SM',['table_alias'=>'sm_accounts'])->addCondition('member_id',$q->getField('member_id'))->setLimit(1)->fieldQuery('AccountNumber');
		});

		$account_model->addExpression('no_of_emi')->set(function($m,$q){
			return $m->refSQL('Premium')->count();
		});

		$account_model->addExpression('emi_amount')->set(function($m,$q){
			return $m->refSQL('Premium')->setLImit(1)->fieldQuery('Amount');
		});
		$account_model->addExpression('maturity_date')->set(function($m,$q){
			return $m->refSQL('Premium')->setOrder('id','desc')->setLImit(1)->fieldQuery('DueDate');
		});

		// $account_model->addExpression('due_premium_count')->set(function($m,$q){
		// 	$p_m = $m->refSQL('Premium')
		// 				->addCondition('PaidOn',null);
		// 	$p_m->addCondition('DueDate','<',$m->api->nextDate($this->app->now));
		// 	return $p_m->count();
		// });

		// $account_model->addExpression('paid_premium_count')->set(function($m,$q){
		// 	$p_m=$m->refSQL('Premium')
		// 				->addCondition('PaidOn','<>',null);
		// 	$p_m->addCondition('DueDate','<',$m->api->nextDate($this->app->today));
		// 	return $p_m->count();
		// })->sortable(true);

		$account_model->addExpression('due_premium_amount')->set(function($m,$q){
			return $q->expr('[0]*[1]',[$m->getElement('due_premium_count'),$m->getElement('emi_amount')]);
		});

		// $account_model->addExpression('due_panelty')->set(function($m,$q){
		// 	$trans_type = $this->add('Model_TransactionType')->tryLoadBy('name',TRA_PENALTY_ACCOUNT_AMOUNT_DEPOSIT);
			
		// 	$tr_m_due = $m->add('Model_TransactionRow',array('table_alias'=>'charged_panelty_tr'));
		// 	$tr_m_due->addCondition('transaction_type_id',$trans_type->id); 
		// 	$tr_m_due->addCondition('account_id',$q->getField('id'));
		// 	$tr_m_due->addCondition('created_at','<',$this->app->nextDate($this->app->today));

		// 	$trans_type = $this->add('Model_TransactionType')->tryLoadBy('name',TRA_PENALTY_AMOUNT_RECEIVED);
			
		// 	$tr_m_received = $m->add('Model_TransactionRow',array('table_alias'=>'received_panelty_tr'));
		// 	$tr_m_received->addCondition('transaction_type_id',$trans_type->id); 
		// 	$tr_m_received->addCondition('account_id',$q->getField('id'));
		// 	$tr_m_received->addCondition('created_at','<',$this->app->nextDate($this->app->today));

		// 	return $q->expr('(IFNULL([0],0)-IFNULL([1],0))',[$tr_m_due->sum('amountDr'),$tr_m_received->sum('amountCr')]);
		// });

		// $account_model->addExpression('total_cr')->set(function($m,$q){
		// 	$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
		// 	$tr_m->addCondition('account_id',$q->getField('id'));
		// 	return $received = $tr_m->sum('amountCr');
		// });

		// $account_model->addExpression('other_charges')->set(function($m,$q){
		// 	$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
		// 	$tr_m->addCondition('transaction_type_id',[13, 46, 39]); // JV, TRA_VISIT_CHARGE, LegalChargeReceived
		// 	$tr_m->addCondition('account_id',$q->getField('id'));
		// 	return $tr_m->sum('amountDr');
		// });

		// $account_model->addExpression('premium_amount_received')->set(function($m,$q){
		// 	return $premium_paid = $q->expr('([0]*[1])',[$m->getElement('paid_premium_count'),$m->getElement('emi_amount')]);
		// });

		// $account_model->addExpression('penalty_amount_received')->set(function($m,$q){
		// 	$trans_type = $this->add('Model_TransactionType')->tryLoadBy('name',TRA_PENALTY_AMOUNT_RECEIVED);
			
		// 	$tr_m_received = $m->add('Model_TransactionRow',array('table_alias'=>'other_received_panelty_tr'));
		// 	$tr_m_received->addCondition('transaction_type_id',$trans_type->id); 
		// 	$tr_m_received->addCondition('account_id',$q->getField('id'));
		// 	$tr_m_received->addCondition('created_at','<',$this->app->nextDate($this->app->today));
		// 	return $tr_m_received->sum('amountCr');
		// });

		// $account_model->addExpression('other_received')->set(function($m,$q){
		// 	return $q->expr('(IFNULL([0],0)-(IFNULL([1],0)+IFNULL([2],0)))',[$m->getElement('total_cr'),$m->getElement('premium_amount_received'),$m->getElement('penalty_amount_received')]);
		// });

		// $account_model->addExpression('other_charges_due')->set(function($m,$q){
		// 	return $q->expr('(IFNULL([0],0)-IFNULL([1],0))',[$m->getElement('other_charges'),$m->getElement('other_received')]);
		// });

		// $account_model->addExpression('total_due')->set(function($m,$q){
		// 	return $q->expr('(IFNULL([0],0)+IFNULL([1],0)+IFNULL([2],0))',[$m->getElement('due_premium_amount'),$m->getElement('due_panelty'),$m->getElement('other_charges_due')]);
		// });

		$account_model->addExpression('current_balance')->set('(CurrentBalanceDr-CurrentBalanceCr)');

		$account_model->addExpression('last_hearing_stage')->set(function($m,$q){
			return $this->add('Model_LegalCaseHearing')
					->addCondition('account_id',$m->getElement('id'))
					->setLimit(1)
					->fieldQuery('stage');
		});
		$account_model->addExpression('account_guarantor')->set(function($m,$q){
			return $m->refSQL('AccountGuarantor')->setLimit(1)->fieldQuery('member');
		});
		$account_model->addExpression('guarantor_fathername')->set(function($m,$q){
			$ag = $m->add('Model_AccountGuarantor');
			$mj = $ag->join('members','member_id');
			$mj->addField('FatherName');
			
			$ag->addCondition('account_id',$q->getField('id'));
			$ag->setLimit(1);
			return $ag->fieldQuery('FatherName');
					
		});
		$account_model->addExpression('gurantor_phone_number')->set(function($m,$q){
			$ag = $m->add('Model_AccountGuarantor');
			$mj = $ag->join('members','member_id');
			$mj->addField('PhoneNos');
			$ag->addCondition('account_id',$q->getField('id'));
			$ag->setLimit(1);
			return $ag->fieldQuery('PhoneNos');
		});
		$account_model->addExpression('gurantor_Address')->set(function($m,$q){
			$ag = $m->add('Model_AccountGuarantor');
			$mj = $ag->join('members','member_id');
			$mj->addField('PermanentAddress');
			$ag->addCondition('account_id',$q->getField('id'));
			$ag->setLimit(1);
			return $ag->fieldQuery('PermanentAddress');
		});
		

		$grid_column_array = ['AccountNumber','member','FatherName','PermanentAddress','landmark','tehsil','district','PhoneNos','dealer','member_sm_account','bike_surrendered_on','bike_auctioned_on','Amount','no_of_emi','emi_amount','premium_amount_received','current_balance','created_at','ActiveStatus','maturity_date','last_hearing_stage','account_guarantor','guarantor_fathername','gurantor_phone_number','gurantor_Address'];

		//$grid_column_array = ['AccountNumber','member','FatherName','PermanentAddress','landmark','tehsil','district','PhoneNos','dealer','member_sm_account','bike_surrendered_on','Amount','no_of_emi','emi_amount','premium_amount_received','current_balance','created_at','ActiveStatus','maturity_date','last_hearing_stage'];
		// $grid_column_array = ['AccountNumber','member','FatherName','PermanentAddress','landmark','tehsil','district','PhoneNos','dealer','member_sm_account','bike_surrendered_on','Amount','no_of_emi','emi_amount','due_premium_amount','due_panelty','other_charges','total_cr','premium_amount_received','penalty_amount_received','other_received','other_charges_due','total_due','created_at','ActiveStatus','last_hearing_stage'];

		$grid = $this->add('Grid_AccountsBase')->addSno();
		if($this->api->stickyGET('filter')){
			if($this->api->stickyGET('dealer')){
				$account_model->addCondition('dealer_id',$_GET['dealer']);
			}

			if($this->api->stickyGET('last_hearing_stage')){
				$account_model->addCondition('last_hearing_stage',$_GET['last_hearing_stage']);
			}

			foreach ($document as $junk) {
				$doc_id = $document->id;
				if($this->api->stickyGET('doc_'.$document->id)){
					$this->api->stickyGET('doc_'.$document->id);
					$account_model->addExpression($this->api->normalizeName($document['name']))->set(function($m,$q)use($doc_id ){
						return $m->refSQL('DocumentSubmitted')->addCondition('documents_id',$doc_id )->fieldQuery('Description');
					});
					$grid_column_array[] = $this->api->normalizeName($document['name']);
				}
			}

			switch ($this->app->stickyGET('loan_type')) {
				case 'vl':
					$account_model->addCondition('AccountNumber','like','%vl%');
					$account_model->addCondition('AccountNumber','not like','%fvl%');
					break;
				case 'pl':
					$account_model->addCondition('AccountNumber','like','%pl%');
					break;
				case 'fvl':
					$account_model->addCondition('AccountNumber','like','%FVL%');
					break;
				case 'sl':
					$account_model->addCondition('AccountNumber','like','%SL%');
					break;
				case 'hl':
					$account_model->addCondition('AccountNumber','like','%HL%');
					break;
				case 'other':
					$account_model->addCondition('AccountNumber','not like','%hl%');
					$account_model->addCondition('AccountNumber','not like','%pl%');
					$account_model->addCondition('AccountNumber','not like','%vl%');
					// $account_model->_dsql()->where('(accounts.AccountNumber not like "%pl%" and accounts.AccountNumber not like "%pl%")');
					break;
			}

			switch ($this->app->stickyGET('legal_status')) {
				case 'is_in_legal':
					$account_model->addCondition('is_in_legal',true);
					$account_model->addCondition('is_in_arbitration',false);
					if($this->app->stickyGET('from_date'))
						$account_model->addCondition('legal_filing_date','>=',$_GET['from_date']);
					if($this->app->stickyGET('to_date'))
						$account_model->addCondition('legal_filing_date','<',$this->app->nextDate($_GET['to_date']));
					$grid_column_array[]= 'legal_filing_date';
					break;
				case 'is_in_arbitration':
					$account_model->addCondition('is_in_legal',false);
					$account_model->addCondition('is_in_arbitration',true);
					if($this->app->stickyGET('from_date'))
						$account_model->addCondition('arbitration_on','>=',$_GET['from_date']);
					if($this->app->stickyGET('to_date'))
						$account_model->addCondition('arbitration_on','<',$this->app->nextDate($_GET['to_date']));
					$grid_column_array[]= 'arbitration_on';
					break;
				case 'is_in_arbitration_process':
					$account_model->addCondition('is_given_for_legal_process',true);
					$account_model->addCondition('legal_case_not_submitted_reason','<>','');
					$account_model->addCondition('legal_case_not_submitted_reason','<>',null);
					$account_model->addCondition('is_in_arbitration',false);
					$account_model->addCondition('is_in_legal',false);
					$account_model->addCondition('is_legal_case_finalised',false);

					if($this->app->stickyGET('from_date'))
						$account_model->addCondition('legal_process_given_date','>=',$_GET['from_date']);
					if($this->app->stickyGET('to_date'))
						$account_model->addCondition('legal_process_given_date','<',$this->app->nextDate($_GET['to_date']));
					$grid_column_array[]= 'legal_process_given_date';
					break;
				case 'is_in_legal_process':
					$account_model->addCondition('is_in_legal',false);
					$account_model->addCondition('is_in_arbitration',false);
					$account_model->addCondition('is_given_for_legal_process',true);
					$account_model->addCondition([['legal_case_not_submitted_reason',''],['legal_case_not_submitted_reason',null]]);

					if($this->app->stickyGET('from_date'))
						$account_model->addCondition('legal_process_given_date','>=',$_GET['from_date']);
					if($this->app->stickyGET('to_date'))
						$account_model->addCondition('legal_process_given_date','<',$this->app->nextDate($_GET['to_date']));
					$grid_column_array[]= 'legal_process_given_date';
					break;
				
				default:
					$account_model->addCondition([['is_in_legal',true],['is_in_arbitration',true],['is_given_for_legal_process',true]]);
					$grid_column_array[]= 'legal_filing_date';
					$grid_column_array[]= 'arbitration_on';
					$grid_column_array[]= 'legal_process_given_date';
					$grid->add('View_Error',null,'grid_buttons')->set('Date Range is not effective in All data');
					// if($this->app->stickyGET('from_date'))
					// 	$account_model->addCondition('legal_process_given_date','>=',$_GET['from_date']);
					// if($this->app->stickyGET('to_date'))
					// 	$account_model->addCondition('legal_process_given_date','<',$this->app->nextDate($_GET['from_date']));
					
					break;
			}

			switch ($this->app->stickyGET('account_status')) {
				case 'Active':
					$account_model->addCondition('ActiveStatus',true);
					break;
				case 'InActive':
					$account_model->addCondition('ActiveStatus',false);
					break;
			}


		}else{
			$account_model->addCondition('id',-1);
		}

		// $account_model->addCondition([['cheque_returned_on','<>',""],['cheque_returned_on','<>',null]]);
		// $account_model->addCondition('is_legal_case_finalised',false);
		// $account_model->addCondition('is_in_arbitration',false);

		$grid->setModel($account_model,$grid_column_array);
		$grid->addPaginator(100);

		if($form->isSubmitted()){
			$send = array('filter'=>1,'dealer'=>$form['dealer'],'to_date'=>$form['to_date']?:0,'from_date'=>$form['from_date']?:0,'account_status'=>$form['account_status'],'legal_status'=>$form['legal_status'],'loan_type'=>$form['loan_type'],'last_hearing_stage'=>$form['last_hearing_stage']?:0);
			foreach ($document as $junk) {
				if($form['doc_'.$document->id])
					$send['doc_'.$document->id] = $form['doc_'.$document->id];
			}
			$grid->js()->reload($send)->execute();
		}

	}
}

<?php
class page_reports_member_activesmsb extends Page {
	public $title="Active SM Account WITH SB Account";
	
	function init(){
		parent::init();

		$till_date="";
		
		$filter = $this->api->stickyGET('filter');
		if($_GET['as_on_date']){
			$this->api->stickyGET('as_on_date');
			$till_date=$this->api->nextDate($_GET['as_on_date']);
		}

		/*FILTER FORM*/
		$form=$this->add('Form');
		$form->addField('DatePicker','as_on_date');

		$form->addSubmit('GET List');

		/*GRID ACCOUNT BASE*/
		// $grid = $this->add('Grid_AccountsBase');
		$grid=$this->add('Grid_Report_SMAccountWithClosingBalance',array('as_on_date'=>$till_date));
		
		$grid->add('H3',null,'grid_buttons')->set('Account Close Report As On '. date('d-M-Y',strtotime($till_date)));
		
		$member = $this->add('Model_Member');
		$sm_acc_j = $member->join('accounts.member_id');
		$sm_acc_j->addField('sm_account_type','account_type');
		$sm_acc_j->addField('sm_status','ActiveStatus');
		$sm_acc_j->addField('acc_created_at','created_at');
		$sm_acc_j->addField('OpeningBalanceDr');
		$sm_acc_j->addField('OpeningBalanceCr');
		$sm_acc_j->addField('SM_account_branch_id','branch_id');

		$member->addExpression('smAccountNumber')->set(function($m,$q){
			return $this->add('Model_Account_SM')
						->addCondition('member_id',$q->getField('id'))
						// ->fieldQuery('AccountNumber');
						->_dsql()->del('fields')
						->field('GROUP_CONCAT(AccountNumber)');
		});
		
		$member->addExpression('SBAccount_type')->set(function($m,$q){
			return $this->add('Model_Account_SavingAndCurrent',['table_alias'=>'rec_acc_tbl'])
						->addCondition('account_type',"Saving")
						->addCondition('member_id',$q->getField('id'))
						->addCondition('ActiveStatus',true)
						->_dsql()->del('fields')
						->field('GROUP_CONCAT(AccountNumber)');
		})->sortable(true);


		$member->addCondition('sm_account_type','SM');
		$member->addCondition('sm_status',true);
		// $member->addCondition('RecurringAccount_type',"Recurring");
		// $member->addCondition('account_type','!=',['FD',ACCOUNT_TYPE_DDS,ACCOUNT_TYPE_RECURRING,LOAN_AGAINST_DEPOSIT]);
					// ->addCondition('ActiveStatus',false)


		/*Form Filter Apply Condition*/
		if($filter){
			if($_GET['as_on_date'])
				$member->addCondition('created_at','<',$this->api->nextDate($_GET['as_on_date']));
		}/*else{
			$member->addCondition('id',-1);
		}*/
		$grid->addColumn('AccountBranch');

		$model_array = ['member_no','member_name_only','created_at','smAccountNumber','SM_account_branch_id','AccountBranch','SBAccount_type'];






		$grid->addSno();
		$grid->addPaginator(1000);
		$grid->addQuickSearch(array('member_no','SBAccount_type'));

		// $grid->add('Controller_xExport',array('fields'=>$model_array,'output_filename'=>'SM ACCOUNT LIST'.' lilst_as_on '. $till_date.".csv"));
		// echo "<pre>";
		// print_r($member);
		// echo "</pre>";

		$grid->setModel($member,$model_array);

		$grid->addMethod('format_AccountBranch',function($g,$f){
			$sm_branch_id = $g->model['SM_account_branch_id'];
			$branch_m = $this->add('Model_Branch');
			// $branch_m->addCondition('id',$sm_branch_id);
			$branch_m->load($sm_branch_id);
			$g->current_row[$f] = $branch_m['Code'];
		});
		$grid->addFormatter('AccountBranch','AccountBranch');
		$grid->removeColumn('SM_account_branch_id');
		$grid->removeColumn('closing_balance_of_account');
		$grid->addOrder()->move('AccountBranch','before','SBAccount_type')->now();
		/*Form Submission*/
		if($form->isSubmitted()){
			$grid->js()->reload(
								array('as_on_date'=>$form['as_on_date']?:0,
									'filter'=>1
								))->execute();
		}

		// $this->member->addExpression('sm_count')->set(function($m,$q){
		// 	return  $this->add('Model_Account_SM',['table_alias'=>'sm_accounts'])->addCondition('member_id',$q->getField('id'))->count();
		// });
		// $this->grid = $this->add('Grid');
		// $this->grid->addSno();
		// $this->grid->addPaginator(500);


		// $this->member->addExpression('saving_and_current_accounts')->set(function($m,$q){
		// 	return $this->add('Model_Account_SavingAndCurrent',['table_alias'=>'saving_accounts'])
		// 		->addCondition('member_id',$q->getField('id'))
		// 		->addCondition('ActiveStatus',true)
		// 		->_dsql()->del('fields')
		// 		->field('GROUP_CONCAT(AccountNumber)');
		// });


		// $this->member->addExpression('sb_disactive')->set(function($m,$q){
			
		// 		return  $this->add('Model_Account_SavingAndCurrent',['table_alias'=>'saving_accounts'])->addCondition('ActiveStatus',false)->addCondition('member_id',$q->getField('id'))->count();
		// });


		// // $this->member->addExpression('loan_account')->set(function($m,$q){
		// // 	return $this->add('Model_Account_Loan')
		// // 				->addCondition('member_id',$q->getField('id'))
		// // 				->addCondition('ActiveStatus',false)
		// // 				->_dsql()->del('fields')
		// // 				->field('GROUP_CONCAT(AccountNumber)');
		// // });


		// // $this->member->addExpression('rd_account')->set(function($m,$q){
		// // 	return $m->add('Model_Account_Recurring')
		// // 				->addCondition('member_id',$q->getField('id'))
		// // 				->addCondition('ActiveStatus',false)
		// // 				->_dsql()->del('fields')
		// // 				->field('GROUP_CONCAT(AccountNumber)');
		// // });

		// $this->member->addExpression('sm_accounts')->set(function($m,$q){
		// 	return $this->add('Model_Account_SM',['table_alias'=>'sm_accounts'])
		// 		->addCondition('member_id',$q->getField('id'))
		// 		->addCondition('ActiveStatus',true)
		// 		->_dsql()->del('fields')
		// 		->field('GROUP_CONCAT(AccountNumber)');
		// });

		// $this->member->addExpression('non_active_accounts')->set(function($m,$q){
			

		// 		return $m->add('Model_Account')
		// 			->addCondition('member_id',$q->getField('id'))
		// 			->addCondition('account_type',['FD',ACCOUNT_TYPE_DDS,ACCOUNT_TYPE_RECURRING,LOAN_AGAINST_DEPOSIT])
		// 			->addCondition('ActiveStatus',false)
		// 			->_dsql()->del('fields')
		// 			->field('GROUP_CONCAT(AccountNumber)');
		// });

		



		// $this->member->addCondition('sm_count','>','0');
		// $this->member->addCondition('sm_accounts','!=','');
		// //$this->member->addCondition('non_active_accounts','!=','');
		// $this->member->addCondition('sb_disactive','<','1');
		// $this->grid->setModel($this->member,['member_no','sm_accounts','saving_and_current_accounts','non_active_accounts','name','PhoneNos','PermanentAddress']);
	}


	// function init(){
	// 	parent::init();

	// 	$till_date=$this->api->today;
	// 	if($_GET['to_date']){
	// 		$till_date=$_GET['to_date'];
	// 	}
	// 	$form=$this->add('Form');
	// 	$form->addField('DatePicker','from_date');
	// 	$form->addField('DatePicker','to_date');
	// 	$form->addField('dropdown','type')->setValueList(array('Recurring'=>'Recurring','DDS'=>'DDS','MIS'=>'MIS','FD'=>'FD','0'=>'All'));
	// 	$form->addSubmit('GET List');


	// 	$grid=$this->add('Grid_AccountsBase');
	// 	$grid->add('H3',null,'grid_buttons')->set('Deposit Member Insurance Report As On '. date('d-M-Y',strtotime($till_date))); 

	// 	$accounts_model=$this->add('Model_Account');
	// 	$accounts_model->add('Controller_Acl');
	// 	$accounts_model->setOrder('SchemeType,created_at');
	// 	$accounts_model->addCondition(
	// 			$accounts_model->dsql()->orExpr()
	// 				->where('SchemeType',ACCOUNT_TYPE_RECURRING)
	// 				->where('SchemeType',ACCOUNT_TYPE_FIXED)
	// 				->where('SchemeType',ACCOUNT_TYPE_DDS)
	// 		);	

	// 	$accounts_model->addCondition('DefaultAC',false);

	// 	if($_GET['filter']){
	// 		$this->api->stickyGET('filter');

	// 		if($_GET['from_date']){
	// 			$this->api->stickyGET('from_date');
	// 			$accounts_model->addCondition('created_at','>=',$_GET['from_date']);
	// 		}
	// 		if($_GET['to_date']){
	// 			$this->api->stickyGET('to_date');
	// 			$accounts_model->addCondition('created_at','<=',$_GET['to_date']);
	// 		}
	// 		if($_GET['type']){
	// 			$this->api->stickyGET('type');
	// 			$accounts_model->addCondition('account_type',$_GET['type']);
	// 		}

	// 	}else{
	// 		$accounts_model->addCondition('id',-1);
	// 	}

	// 	$accounts_model->addExpression('member_name')->set(function($m,$q){
	// 		return $m->refSQL('member_id')->fieldQuery('name');
	// 	})->sortable(true);

	// 	$accounts_model->addExpression('father_name')->set(function($m,$q){
	// 		return $m->refSQL('member_id')->fieldQuery('FatherName');
	// 	});

	// 	$accounts_model->addExpression('address')->set(function($m,$q){
	// 		return $m->refSQL('member_id')->fieldQuery('PermanentAddress');
	// 	});

	// 	$accounts_model->addExpression('age')->set(function($m,$q){
	// 		return $m->refSQL('member_id')->fieldQuery('DOB');
	// 	});


	// 	$accounts_model->addExpression('phone_nos')->set(function($m,$q){
	// 		return $m->refSQL('member_id')->fieldQuery('PhoneNos');
	// 	});

	// 	$grid->setModel($accounts_model,array('AccountNumber','scheme','member_name','father_name','address','phone_nos','age','Nominee','RelationWithNominee','Amount'));

	// 	$grid->addMethod('format_age',function($g,$f){
	// 		$age=array();
	// 		if($g->current_row[$f] !='0000-00-00 00:00:00'){
	// 			$age = $g->api->my_date_diff($g->api->today,$g->current_row[$f]?:$g->api->today);
	// 		}
	// 		$g->current_row[$f] = $g->current_row[$f]? $age['years']:"";
	// 	});

	// 	$grid->addFormatter('age','age');
	// 	$grid->addColumn('text','insurance_amount');

	// 	$paginator = $grid->addPaginator(50);
	// 	$grid->skip_var = $paginator->skip_var;

	// 	$grid->addSno();
	// 	// $grid->removeColumn('scheme');

	// 	// $js=array(
	// 	// 	$this->js()->_selector('.mymenu')->parent()->parent()->toggle(),
	// 	// 	$this->js()->_selector('#header')->toggle(),
	// 	// 	$this->js()->_selector('#footer')->toggle(),
	// 	// 	$this->js()->_selector('ul.ui-tabs-nav')->toggle(),
	// 	// 	$this->js()->_selector('.atk-form')->toggle(),
	// 	// 	);

	// 	// $grid->js('click',$js);
	


	// 	if($form->isSubmitted()){
	// 		$send = array('from_date'=>$form['from_date']?:0,'to_date'=>$form['to_date']?:0,'type'=>$form['type'],'filter'=>1);
	// 		$grid->js()->reload($send)->execute();

	// 	}	
	
	// }
}
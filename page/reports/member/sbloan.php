<?php

class page_reports_member_sbloan extends Page {
	public $title='Member Loan Detail Report';
	
	function page_index(){
		// parent::init();
		$period = $this->app->stickyGET('period');
		$form = $this->add('Form');
		$type_array = [];
		foreach (explode(",", MEMBER_TYPES) as $key => $value) {
			$type_array[$value] = $value; 
		}

		$form->addField('Dropdown','type')->setValueList(array_merge(["All"=>"All"],$type_array));
		$prev6month = $this->api->previous6Month($this->api->today);
		$prevyear = $this->api->previousYear($this->api->today);
		$form->addField('DropDown','period')->setValueList(array($prev6month=>'6 MONTH',$prevyear=>'1 YEAR'))->setEmptyText('Please Select Transaction Period');
		$bank_field = $form->addField('Dropdown','bank')->setEmptyText('All Banks');
		$bank_field->setModel('Bank');
		$form->addField('dropdown','status')->setValueList(array('all'=>'All','0'=>'InActive','1'=>'Active'));
		$form->addField('Line','pan_no');
		$form->addField('Line','adhar_no');
		$form->addSubmit('Get List');
		
		$member_model = $this->add('Model_Member');
		// $tr_model->addCondition('branch_id',$this->api->current_branch->id);

		$member_account_join = $member_model->join('accounts.member_id');
		$member_account_join->addField('AccountNumber');
		$member_account_join->addField('account_id','id');
		$member_account_join->addField('OpeningBalanceDr');
		$member_account_join->addField('OpeningBalanceCr');
		$member_account_join->addField('account_active_status','ActiveStatus');
		$member_account_join->addField('member_account_type','account_type');
		$member_account_join->addField('acc_created_at','created_at');
		$member_account_join->addField('account_scheme_id','scheme_id');
		$member_account_join->addField('DefaultAC');
		$scheme_join = $member_account_join->leftJoin('schemes','scheme_id');
		$scheme_join->addField('SchemeType');
		$scheme_join->addField('scheme_name','name');

		$member_model->addCondition('account_active_status',true);
		$member_model->addCondition('member_account_type','Saving');
		$member_model->addCondition('DefaultAC',false);
		// $member_model=$this->add('Model_Member');
		$member_model->setOrder('created_at','desc');
		$member_model->addExpression('bank_a_id')->set($member_model->refSQL('bankbranch_a_id')->fieldQuery('bank_id'));
		$member_model->addExpression('bank_b_id')->set($member_model->refSQL('bankbranch_b_id')->fieldQuery('bank_id'));
		
		$member_model->addExpression('member_account_type')->set(function($m,$q){
			return $this->add('Model_Account')
						->addCondition('member_id',$q->getField('id'))
						->setOrder('created_at','desc')
						->setLimit(1)
						->fieldQuery('created_at');
		});
		$member_model->addExpression('scheme')->set(function($m,$q){
			return $this->add('Model_Account')
						->addCondition('member_id',$q->getField('id'))
						->addCondition('account_type','Saving')
						->fieldQuery('scheme');
		});

		$member_model->addExpression('last_interest_amount')->set(function($m,$q){
			return $this->add('Model_TransactionRow')
						->addCondition('account_id',$m->getElement('account_id'))
						->addCondition('transaction_type','InterestPostingsInSavingAccounts')
						->setOrder('created_at','desc')
						->setLimit(1)
						->fieldQuery('amountCr');
		});

		$member_model->addExpression('last_transaction_date')->set(function($m,$q)use($period){
			// if($period)
			// 	// return date("Y-m-d", strtotime(date("Y-m-d", strtotime($period))));
			// 	return date("Y-m-d", strtotime("-6 months", strtotime($period)));
			// else
			// 	return "123";
			return $this->add('Model_TransactionRow')
						->addCondition('account_id',$m->getElement('account_id'))
						->addCondition('transaction_type','<>','InterestPostingsInSavingAccounts')
						->setOrder('created_at','desc')
						->setLimit(1)
						->fieldQuery('created_at');
		});

	$member_model->addExpression('deposite_account')->set(function($m,$q){
			$deposite_m =$this->add('Model_Active_Account',['table_alias'=>'dp_accounts']);
			return 	$deposite_m->addCondition('member_id',$q->getField('id'))
				// ->addCondition('ActiveStatus',true)
				->addCondition(
					$deposite_m ->dsql()->orExpr()
					->where('account_type','DDS')
					->where('account_type','FD')
					->where('account_type','Recurring'))
				->_dsql()->del('fields')
				->field('GROUP_CONCAT(AccountNumber)');
		});
		// $member_model->addExpression('deposite_account')->set(function($m,$q){
		// 	 $deposite_m = $this->add('Model_Active_Account');
		// 		$deposite_m->addCondition('member_id',$q->getField('id'));
		// 		$deposite_m->addCondition(
		// 			$deposite_m ->dsql()->orExpr()
		// 			->where('account_type','DDS2')
		// 			->where('account_type','FixedAndMis')
		// 			->where('account_type','Recurring'));
		// 		$deposite_m->setOrder('created_at','desc');
		// 		$deposite_m->setLimit(1);
		// 	return $deposite_m->fieldQuery('AccountNumber');
		// });

		$member_model->addExpression('active_loan_account')->set(function($m,$q){
			return $this->add('Model_Account_Loan')
						->addCondition('ActiveStatus',true)
						->addCondition('member_id',$q->getField('id'))
						->setOrder('created_at','desc')
						->setLimit(1)
						->fieldQuery('AccountNumber');
		});

		$member_model->addExpression('active_loan_amount')->set(function($m,$q){
			return $this->add('Model_Account_Loan')
						->addCondition('ActiveStatus',true)
						->addCondition('member_id',$q->getField('id'))
						->setOrder('created_at','desc')
						->setLimit(1)
						->fieldQuery('Amount');
		});

		// $member_model->addExpression('last_loan_account')->set(function($m,$q){
		// 	return $this->add('Model_Account')
		// 				->addCondition('member_id',$q->getField('id'))
		// 				->setOrder('created_at','desc')
		// 				->setLimit(1)
		// 				->fieldQuery('AccountNumber');
		// });

		// $member_model->addExpression('last_loan_amount')->set(function($m,$q){
		// 	return $this->add('Model_Account')
		// 				->addCondition('member_id',$q->getField('id'))
		// 				->setOrder('created_at','desc')
		// 				->setLimit(1)
		// 				->fieldQuery('Amount');
		// });


		$grid=$this->add('Grid');
		if($_GET['filter']){
			// throw new \Exception(strtotime($period), 1);
			
			$this->api->stickyGET('filter');
			$this->api->stickyGET('status');
			$this->api->stickyGET('type');
			$this->api->stickyGET('bank');
			$this->api->stickyGET('pan_no');
			$this->api->stickyGET('adhar_no');

			if($_GET['status'] !=='all')
				$member_model->addCondition('is_active',$_GET['status']==0?false:true);
			
			if($_GET['type']){
				$this->api->stickyGET('type');
				if($_GET['type'] != "All"){
					$member_model->addCondition('memebr_type',$_GET['type']);
				}
			}
			if($_GET['bank']){
				$this->api->stickyGET('bank');
				$member_model->addCondition([['bank_a_id',$_GET['bank']],['bank_b_id',$_GET['bank']]]);
			}

			if($_GET['pan_no']){
				$this->app->stickyGET('pan_no');
				$member_model->addCondition('pan_no',$_GET['pan_no']);
			}

			if($_GET['adhar_no']){
				$this->app->stickyGET('adhar_no');
				$member_model->addCondition('AdharNumber',$_GET['adhar_no']);
			}

		}else{
			$member_model->addCondition('id',-1);
		}
		// $grid->add('H3',null,'grid_buttons')->set('Member Repo As On '. date('d-M-Y',strtotime($till_date))); 
		$grid->setModel($member_model,array('AccountNumber','scheme_name','member_no','branch'/*,'gender'*/,'name','FatherName','last_transaction_date','last_interest_amount','deposite_account','active_loan_account','active_loan_amount'/*,'CurrentAddress','landmark','tehsil','city','PhoneNos','created_at','DOB','last_loan_account','last_loan_date','last_loan_amount'*/,'is_active'));
		$grid->addPaginator(1000);
		$grid->addQuickSearch(array('AccountNumber','member_no','name'));
		$self=$this;

		$self=$this;
		$grid->addColumn('comment');
		$grid->addMethod('format_comment',function($g,$f)use($self){
			// throw new \Exception($g->model->id, 1);
			$comment_model=$self->add('Model_Comment');//->load($g->model->id);
			$comment_model->addCondition('member_id',$g->model->id);
			$comment_model->setOrder('created_at','desc');
			$comment_model->tryLoadAny();
			$narration=$comment_model->get('narration');
			$g->current_row[$f]=$narration;
		});

		$grid->addMethod('init_image2',function($g){
			$this->js('click')->_selector('img')->univ()->frameURL('IMAGE',[$this->app->url('image'),'image_id'=>$this->js()->_selectorThis()->data('sig-image-id') ]);
		});

		$grid->addMethod('format_image2',function($g,$f)use($self){
			$g->current_row_html[$f]=$g->model['doc_thumb_url']?'<img src="'.$g->model['doc_thumb_url'].'" data-sig-image-id="'.$g->model['sig_image_id'].'"/>':'';
		});
		
		$grid->addFormatter('comment','comment');
		$grid->addFormatter('deposite_account','wrap');
		$grid->addColumn('expander','details');
		$grid->addColumn('expander','accounts');
		$grid->addColumn('expander','guarantor_in');
		$grid->removeColumn('sig_image_id');

		// $js=array(
		// 	$this->js()->_selector('.mymenu')->parent()->parent()->toggle(),
		// 	$this->js()->_selector('#header')->toggle(),
		// 	$this->js()->_selector('#footer')->toggle(),
		// 	$this->js()->_selector('ul.ui-tabs-nav')->toggle(),
		// 	$this->js()->_selector('.atk-form')->toggle(),
		// 	);

		// $grid->js('click',$js);

		if($form->isSubmitted()){
			$send = array('pan_no'=>$form['pan_no'],'adhar_no'=>$form['adhar_no'],'type'=>$form['type'],'bank'=>$form['bank'],'status'=>$form['status'],'period'=>$form['period'],'filter'=>1);
			$grid->js()->reload($send)->execute();

		}	
	}
	function page_details(){
		$this->api->stickyGET('members_id');
		$member_model=$this->add('Model_Member');
		$member_model->addCondition('id',$_GET['members_id']);

		$grid=$this->add('Grid');

		$extra_fields=array('branch_id','name','CurrentAddress','tehsil','city','PhoneNos','created_at','is_active','is_defaulter');
		foreach ($extra_fields as $key => $value) {
			$member_model->getElement($value)->system(true);
		}
		$grid->setModel($member_model);
	
	}
	function page_accounts(){
		$this->api->stickyGET('members_id');

		$this->api->stickyGET('members_id');
		$member_model=$this->add('Model_Member');
		$member_model->addCondition('id',$_GET['members_id']);
		$member_model->loadAny();

		$this->add('H4')->setHTML('Accounts Details for <span style="text-transform:capitalize"><u>'.$member_model['name'].'</u></span>');
		$grid=$this->add('Grid');
		$accounts=$member_model->ref('Account');
		$accounts->addExpression('LoanAgainst')->set(function($m,$q){
			$x = $m->add('Model_Account',['table_alias'=>'loan_ag']);
			return $x->addCondition('id',$q->getField('LoanAgainstAccount_id'))->fieldQuery('AccountNumber');
		});
		// $accounts->addCondition('ActiveStatus',true);
		// $accounts->addCondition('MaturedStatus',false);
		$grid->setModel($accounts,array('branch','AccountNumber','LoanAgainst','scheme','agent','Amount','ActiveStatus','MaturedStatus','SchemeType'));

		$grid->addMethod('format_cuBal',function($g,$f){
			$bal = $g->model->getOpeningBalance($on_date=$g->api->nextDate($g->api->today),$side='both',$forPandL=false);
			if($bal['cr'] > $bal['dr']){
				$bal = ($bal['cr'] - $bal['dr']) . ' Cr';
			}else{
				$bal = ($bal['dr'] - $bal['cr']) . ' Dr';
			}

			$g->current_row[$f]=$bal ;
		});

		$grid->addMethod('format_maturityDate',function($g,$f){
			$acc = $this->add('Model_Account_'.$g->model['SchemeType']);
			$acc->load($g->model->id);
			$g->current_row[$f]=$acc['maturity_date'] ;
		});

		$grid->addColumn('maturityDate','maturity_date');
		$grid->addColumn('cuBal','cur_balance');

	}


	function page_guarantor_in(){

		$this->api->stickyGET('members_id');

		$account_model=$this->add('Model_Account');
		$account_model->join('account_guarantors.account_id')->addField('guarantor_member_id','member_id');
		$account_model->addCondition('guarantor_member_id',$_GET['members_id']);
		$account_model->addCondition('ActiveStatus',true);
		$account_model->addCondition('MaturedStatus',false);

		$this->add('H4')->setHTML('Accounts Details for <span style="text-transform:capitalize"><u>'.$this->add('Model_Member')->load($_GET['members_id'])->get('name').'</u></span>');
		$grid=$this->add('Grid');
		$grid->setModel($account_model,array('branch','AccountNumber','scheme','agent','Amount'));
	}

}
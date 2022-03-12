<?php
class page_reports_member_smbankdetails extends Page {
	public $title="Member Bank Details";
	function init(){
		parent::init();


		$form=$this->add('Form');
		$accounts_no_field=$form->addField('autocomplete/Basic','accounts_no');
		$accounts=$this->add('Model_Account');
		$accounts_no_field->setModel($accounts);

		$form->addSubmit('GET List');

		$account_model=$this->add('Model_Account');
		$account_model->addCondition('account_type','<>','Saving');
		echo "string". $account_model->count()->getOne();
		$grid=$this->add('Grid_AccountStatement'); 
		// if($this->api->stickyGET('account_no')){
			// $account_model->load($_GET['account_no']);
		// 	// throw new \Exception(date("d-m-Y"));
			
		// }

		
		
		$member=$this->add('Model_Member');
		$acc_j = $member->join('accounts.member_id');
		$acc_j->addField('member_account_type','account_type');
		$acc_j->addField('account_member_id','member_id');
		$member->addCondition('is_active',true);
		// $member->addCondition('id',$member['account_member_id']);
		$member->addCondition('member_account_type','<>','Saving');
		// $member->addCondition('bank_account_number_1','>',0);
		$member->addCondition(
										$member->dsql()->orExpr()
											->where($member->getElement('bank_account_number_1'),'>',0)
											->where($member->getElement('bank_account_number_2'),'>',0)
							);
		$member->addExpression('tr_amount_cr')->set(function($m,$q)use($account_model){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'trn_amount_cr'));
			$tr_m->addCondition('account_id',$account_model->id);
			$tr_amount_cr = $tr_m->sum('amountCr');
			return $tr_amount_cr;
		});
		$member->addExpression('tr_amount_dr')->set(function($m,$q)use($account_model){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'trn_amount_cr'));
			$tr_m->addCondition('account_id',$account_model->id);
			$tr_amount_cr = $tr_m->sum('amountDr');
			return $tr_amount_cr;
		});

		$member->addExpression('amount')->set(function($m,$q){
			return $q->expr('([0]-[1])',[$m->getElement('tr_amount_cr'),$m->getElement('tr_amount_dr')]);
		});



		$member->addExpression('sm_account')->set(function($m,$q)use($account_model){
				return $acc = $this->add('Model_Account_SM')->addCondition('member_id',$q->getField('id'))->setLimit(1)->fieldQuery('AccountNumber');
		});
		$member->addExpression('saving_account')->set(function($m,$q)use($account_model){
				return $acc = $this->add('Model_Account_SavingAndCurrent')->addCondition('member_id',$q->getField('id'))->setLimit(1)->fieldQuery('AccountNumber');
		});


		$member->addExpression('bank_branch')->set(function($m,$q)use($account_model){
				return $m->refSQL('bankbranch_a_id')->fieldQuery('name');
		});
		$member->addExpression('bank_name')->set(function($m,$q)use($account_model){
				return $m->refSQL('bankbranch_a_id')->fieldQuery('bank');
		});
		$member->addExpression('ifsc')->set(function($m,$q)use($account_model){
				return $m->refSQL('bankbranch_a_id')->fieldQuery('IFSC');
		});
		$date = date('d-m-Y');

		$grid->addSno();


		$grid->setModel($member,array('bank_account_number_1','bank_account_number_2','member_bank','name','amount','payment_mode','current_date','ifsc','bank_branch','bank_name','sm_account','saving_account'));
		
		$grid->addMethod('format_payment_mode',function($grid,$field){
				$grid->current_row[$field]= "RTGS / NEFT";
			});
		$grid->addColumn('payment_mode','payment_mode');
	
		$grid->addMethod('format_current_date',function($grid,$field){
				// $grid->current_row[$field]= "dd-mm-yyyy";
				$grid->current_row[$field]= date('d-M-Y');
			});
		$grid->addColumn('current_date','current_date');
		

		$grid->addPaginator(500);
		$order=$grid->addOrder();
		$order->move('payment_mode','after','amount')->now();
		$order->move('current_date','after','payment_mode')->now();
		$member->_dsql()->group('member_id');
		echo "string". $member->count()->getOne();
		if($form->isSubmitted()){
			$grid->js()->reload(array('account_no'=>$form['accounts_no'],'filter'=>1))->execute();
		}	
	

	}
}

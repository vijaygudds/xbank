<?php
class page_reports_member_bankdetails extends Page {
	public $title="Member Bank Details";
	function init(){
		parent::init();


		$form=$this->add('Form');
		$accounts_no_field=$form->addField('autocomplete/Basic','accounts_no');
		$accounts=$this->add('Model_Account');
		$accounts_no_field->setModel($accounts);

		$form->addSubmit('GET List');

		$account_model=$this->add('Model_Account');

		$grid=$this->add('Grid_AccountStatement'); 
		if($this->api->stickyGET('account_no')){
			$account_model->load($_GET['account_no']);
			// throw new \Exception(date("d-m-Y"));
			
		}

		
		
		$member=$this->add('Model_Member');
		$member->addCondition('id',$account_model['member_id']);

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



		$member->addExpression('account_number')->set(function($m,$q)use($account_model){
				return $acc = $this->add('Model_Account')->addCondition('id',$account_model->id)->setLimit(1)->fieldQuery('AccountNumber');
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


		$grid->setModel($member,array('bank_account_number_1','name','amount','payment_mode','current_date','ifsc','bank_branch','bank_name','account_number'));
		
		$grid->addMethod('format_payment_mode',function($grid,$field){
				$grid->current_row[$field]= "RTGS / NEFT";
			});
		$grid->addColumn('payment_mode','payment_mode');
	
		$grid->addMethod('format_current_date',function($grid,$field){
				// $grid->current_row[$field]= "dd-mm-yyyy";
				$grid->current_row[$field]= date('d-M-Y');
			});
		$grid->addColumn('current_date','current_date');
		


		$order=$grid->addOrder();
		$order->move('payment_mode','after','amount')->now();
		$order->move('current_date','after','payment_mode')->now();

		if($form->isSubmitted()){
			$grid->js()->reload(array('account_no'=>$form['accounts_no'],'filter'=>1))->execute();
		}	
	

	}
}

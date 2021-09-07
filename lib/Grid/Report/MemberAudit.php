<?php

class Grid_Report_MemberAudit extends Grid_AccountsBase{
	public $as_on_date;

	function setModel($model,$fields=null){
		parent::setModel($model,$fields);

		//Code
		$this->addColumn('category');
		$this->addColumn('share');
		$this->addColumn('share_amount');
		$this->addColumn('loan');
		$this->addFormatter('loan','loan');
		$this->addFormatter('share','share');
		$this->addFormatter('share_amount','share_amount');
		
		// $order=$this->addOrder();
		// $order->move('category','before','Occupation')->now();
		

		// $this->addColumn('sm_account_no');
		// $this->addFormatter('sm_account_no','sm_account_no');

		// $this->model->getElement('name')->caption('Member');
		// $this->addFormatter('PermanentAddress','Wrap');
		$this->addSno();
		$paginator = $this->addPaginator(10000);
		$this->skip_var = $paginator->skip_var;

		$this->addQuickSearch(array('AccountNumber'));

		$this->removeColumn('sum');
		$this->removeColumn('member');
		$this->removeColumn('member_id');
		$this->removeColumn('SchemeType');
		$this->removeColumn('OpeningBalanceCr');
		$this->removeColumn('OpeningBalanceDr');
	}

	function format_share_amount($field){
		$amount = $this->model['OpeningBalanceCr']-$this->model['OpeningBalanceDr'];
		$amount = $amount + $this->model['sum'];
		$balance = $amount/*.' CR'*/;
		if($amount < 0)
			$balance = abs($amount)/*.' DR'*/;

		$this->current_row_html[$field] = $balance;
	}

	function format_share($field){
		$amount = $this->model['OpeningBalanceCr']-$this->model['OpeningBalanceDr'];
		$amount = $amount + $this->model['sum'];
		$amount = $amount / 100;
		
		$balance = $amount/*.' CR'*/;
		if($amount < 0)
			$balance = abs($amount)/*.' DR'*/;

		$this->current_row_html[$field] = $balance;
	}

	function format_loan($field){
		$loaner=" ";
		if(!empty($this->model['last_loan_account'])){
			$loaner = "Loaner";
		}		

		$this->current_row_html[$field] = $loaner;
	}

	function format_sm_account_no($field){
		if(!$this->model['member_id'])
			$this->current_row[$field] = "Member Not Found";
			
		$member_model = $this->add('Model_Member')->load($this->model['member_id']);
		$number = $member_model->ref('Account')->addCondition('SchemeType','Default')->addCondition('scheme_name','Share Capital')->fieldQuery('AccountNumber');

		$this->current_row[$field] = $number; 
	}

	// function formatRow(){
	// 	parent::formatRow();
	// }
}

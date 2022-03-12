<?php
class page_reports_member_nonmemberacc extends Page {
	public $title="Member Non Active Account";
	
	function init(){
		parent::init();

		$this->member = $this->add('Model_Member');
		
		$this->member->addCondition('name','Not Like','%Default%');
		$this->member->getElement('PhoneNos')->mandatory(false);
		
		$this->crud = $this->add('xCRUD',['allow_del'=>false,'allow_add'=>false]);
		$this->grid = $this->crud->grid;
		$this->grid->addSno();
		$this->grid->addPaginator(5000);

		$acc_j = $this->member->join('accounts.member_id');
		$acc_j->addField('ActiveStatus');
		

		$this->member->addExpression('non_active_accounts')->set(function($m,$q){
			

				return $m->add('Model_Account')
					->addCondition('member_id',$q->getField('id'))
					// ->addCondition('account_type',['FD',ACCOUNT_TYPE_DDS,ACCOUNT_TYPE_RECURRING,LOAN_AGAINST_DEPOSIT])
					->addCondition('ActiveStatus',false)
					->_dsql()->del('fields')
					->field('GROUP_CONCAT(AccountNumber)');
		});

		$this->member->addExpression('active_accounts')->set(function($m,$q){
			

				return $m->add('Model_Account')
					->addCondition('member_id',$q->getField('id'))
					// ->addCondition('account_type',['FD',ACCOUNT_TYPE_DDS,ACCOUNT_TYPE_RECURRING,LOAN_AGAINST_DEPOSIT])
					->addCondition('ActiveStatus',true)
					->_dsql()->del('fields')
					->field('GROUP_CONCAT(AccountNumber)');
		})->sortable(true);


		if(!$this->crud->isEditing('edit')){
			$this->member->addCondition('is_active',true);
		}
		
		


		$this->member->addCondition('ActiveStatus',false);
		// $this->member->addCondition('non_active_accounts','!='," ");;
		// $this->member->addCondition('active_accounts','');
		$this->member->_dsql()->group('member_id');


		$this->crud->setModel($this->member,['name','is_active','PhoneNos'],['member_no'/*,'non_active_accounts'*/,'name','PhoneNos','active_accounts']);


		$ox=$this->crud->grid->addOrder();
		$ox->move('edit','after','PhoneNos')->now();
	}
	
}
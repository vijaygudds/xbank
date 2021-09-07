<?php

class page_reports_member_audit extends Page {
	public $title="Members Audit Reports";
	function page_index(){
		

		$grid=$this->add('Grid_Report_MemberAudit');

		// $account_model = $this->add('Model_Active_Account');
		
		$member = $this->add('Model_Member');
		// $tr_model->addCondition('branch_id',$this->api->current_branch->id);

		$member_account_join = $member->join('accounts.member_id');
		$member_account_join->addField('AccountNumber');
		$member_account_join->addField('account_id','id');
		$member_account_join->addField('OpeningBalanceDr');
		$member_account_join->addField('OpeningBalanceCr');
		$member_account_join->addField('account_active_status','ActiveStatus');
		$member_account_join->addField('account_type');
		$member_account_join->addField('acc_created_at','created_at');
		$member_account_join->addField('account_scheme_id','scheme_id');

		$scheme_join = $member_account_join->leftJoin('schemes','scheme_id');
		$scheme_join->addField('SchemeType');
		$scheme_join->addField('scheme_name','name');


		$tr_join = $member_account_join->leftJoin('transaction_row.account_id');
		$tr_join->addField('amountCr');
		$tr_join->addField('amountDr');

		$member->addExpression('last_loan_account')->set(function($m,$q){
			return $this->add('Model_Account_Loan')
						->addCondition('member_id',$q->getField('id'))
						->setOrder('created_at','desc')
						->setLimit(1)
						->fieldQuery('AccountNumber');
		})->caption('Loan');
		
		



		$member->addExpression('sum')->set(function($m,$q){
			return $m->dsql()->expr('sum(amountCr - amountDr)'); //$m->getElement('amountCr') - $m->getElement('amountDr');
		});

		// $member->addExpression('sm_no')->set(function($m,$q){
		// 	$acc = $this->add('Model_Account',['table_alias'=>'sm_no']);
		// 	return $acc->addCondition('member_id',$m->getField('member_id'))->addCondition('SchemeType','Default')->addCondition('scheme_name','Share Capital')->setLimit(1)->fieldQuery('AccountNumber');
		// });


		// $fields_array=array('name','FatherName','created_at','acc_created_at','account_active_status','OpeningBalanceDr','OpeningBalanceCr','AccountNumber','account_id','id','last_loan_account');

		$fields_array=array('name','FatherName','AdharNumber','PhoneNos','sum','OpeningBalanceDr','OpeningBalanceCr','DOB','gender','Occupation','AccountNumber','Cast','member','last_loan_account','PermanentAddress');

		/*if($filter){
			
			if($_GET['as_on_date'])
				$member->addCondition('created_at','<',$this->api->nextDate($_GET['as_on_date']));
			
			
		}*//*else{
			$tr_model->addCondition('id',-1);
		}*/
		
		$member->addCondition('SchemeType','Default');
		$member->addCondition('scheme_name','Share Capital');
		// $member->addCondition('account_type','Default');
		// $member->addCondition('account_scheme_id',6);
		$member->addCondition('account_active_status',true);
		$member->_dsql()->group('id');

		// $order=$grid->addOrder();
		// $order->move('category','before','Occupation')->now();
		// $order=$this->addOrder();
		// $order->move('file_charge', 'after','loan_amount')->now();
		
		$member->setOrder('account_id');
		$member->add('Controller_Acl');
		$grid->setModel($member,$fields_array);
		// $grid->addPaginator(500);

		// if($form->isSubmitted()){
		// 	$grid->js()->reload(
		// 						array('as_on_date'=>$form['as_on_date']?:0,
		// 							'filter'=>1
		// 						))->execute();
		// }

	}

}


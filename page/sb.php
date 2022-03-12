<?php

class page_sb extends Page {
	public $title="Saving Accounts List";

	

function init(){
		parent::init();
		$till_date = $this->api->today;
		$from_date = '01-01-1970';
		if($this->app->stickyGET('to_date')) $till_date = $_GET['to_date'];
		if($this->app->stickyGET('from_date')) $from_date = $_GET['from_date'];

		$form = $this->add('Form');
		$form->addField('DatePicker','from_date');
		$form->addField('DatePicker','to_date');
		
		$form->addSubmit('GET List');

		$crud=$this->add('CRUD',['allow_add'=>false,'allow_del'=>false,'allow_edit'=>false]);
		$grid = $crud->grid;
		$grid->add('H3',null,'grid_buttons')->set('Saving Accounts List As On ' . date('d-m-Y',strtotime($_GET['on_date']?:$this->api->today)) );
		
		$account_model=$this->add('Model_Account_SavingAndCurrent');
		$member_join=$account_model->join('members','member_id');
		$member_join->addField('member_name','name');
		$member_join->addField('FatherName');
		$member_join->addField('PhoneNos');
		$member_join->addField('CurrentAddress');
		$member_join->addField('landmark');

		$account_model->addCondition('DefaultAC',false);
		// $account_model->addCondition('ActiveStatus',true);
		


		if($_GET['filter']){
			$this->api->stickyGET('filter');
			$account_model->addExpression('count_row')->set(function($m, $q){
				return $m->refSQL('TransactionRow')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
						->count();
						
			});
			$account_model->addCondition('count_row','<',3);
		}else
			$account_model->addCondition('id',-1);


		$account_model->add('Controller_Acl');
		$crud->setModel($account_model,array('member_name','scheme','ActiveStatus','created_at','Amount','AccountNumber','count_row'));
		$grid->addFormatter('ActiveStatus','grid/inline');

		$grid->addSno();
		$order =$grid->addOrder();
		$order->move('s_no', 'first')->now();
		$paginator = $grid->addPaginator(1000);
		$grid->skip_var = $paginator->skip_var;

		if($form->isSubmitted()){
			$grid->js()->reload(array('from_date'=>$form['from_date'],'to_date'=>$form['to_date']?:0,'filter'=>1))->execute();
		}	
	}
}
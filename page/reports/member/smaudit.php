<?php

class page_reports_member_smaudit extends Page {
	public $title = "SM Audit";

	function init(){
		parent::init();

		$this->member = $this->add('Model_Member');
		$this->member->addExpression('sm_count')->set(function($m,$q){
			return  $this->add('Model_Account_SM',['table_alias'=>'sm_accounts'])->addCondition('member_id',$q->getField('id'))->count();
		});
		$this->grid = $this->add('Grid');
		$this->grid->addSno();
		$this->grid->addPaginator(500);
	}

	function page_index(){
		$this->grid->destroy();
		$tabs = $this->add('Tabs');
		$tabs->addTabURL($this->app->url('./nosm'),'Members Without SM');
		$tabs->addTabURL($this->app->url('./smwithzerobalance'),'Members With Zero balance SM');
		$tabs->addTabURL($this->app->url('./multiplesm'),'Members With multiple SM');
	}

	function page_nosm(){

		$this->member->addCondition('sm_count',0);
		$this->member->addCondition('is_active',true);

		$this->grid->setModel($this->member,['name','member_no']);
	}

	function page_smwithzerobalance(){
		// $this->member->addExpression('sm_accounts')->set(function($m,$q){
		// 	$smacc= $this->add('Model_Account_SM',['table_alias'=>'sm_accounts','with_balance_cr'=>true])
		// 		->addCondition('member_id',$q->getField('id'));
		// 	$smacc->addCondition($q->expr('[0]=0',[$smacc->getElement('tra_cr')]));
		// 	return $smacc->_dsql()->del('fields')
		// 		->field('GROUP_CONCAT(AccountNumber)');
		// });
		// $this->grid->setModel($this->member,['name','sm_accounts']);
		// $this->grid->addPaginator(10);
		$form = $this->add('Form');
		$form->addField('dropdown','status')->setValueList(array('all'=>'All','0'=>'InActive','1'=>'Active'));
		$form->addSubmit("Get List");

		$this->grid->destroy();
		$sm_accounts = $this->add('Model_Account_SM',['with_balance_dr'=>true]);

		$sm_accounts->addCondition('balance_dr',0);
		// $grid=$this->add('Grid');
		// $sm_accounts->addCondition('tra_dr',0);
		$grid = $this->add('Grid');
		$this->api->stickyGET('status');
		$this->api->stickyGET('filter');
		if($_GET['filter']){
			$this->api->stickyGET('status');
			
			if($_GET['status'] !=='all')
				$sm_accounts->addCondition('ActiveStatus',$_GET['status']==0?false:true);

		}else{
			$sm_accounts->addCondition('id',-1);
		}

		$grid->addSno();
		$grid->setModel($sm_accounts,['member_name_only','AccountNumber','balance_cr','balance_dr','ActiveStatus']);
		$grid->addPaginator(100);

		if($form->isSubmitted()){
			$send = array('status'=>$form['status'],'filter'=>1);
			$grid->js()->reload($send)->execute();

		}	
	}

	function page_multiplesm(){
		
		$this->member->addExpression('sm_accounts')->set(function($m,$q){
			return $this->add('Model_Account_SM',['table_alias'=>'sm_accounts'])
				->addCondition('member_id',$q->getField('id'))
				->addCondition('ActiveStatus',true)
				->_dsql()->del('fields')
				->field('GROUP_CONCAT(AccountNumber)');
		});

		$this->member->addCondition('sm_count','>',1);
		$this->grid->setModel($this->member,['name','member_no','sm_count','sm_accounts']);
	}

}

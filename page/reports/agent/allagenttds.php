<?php

/**
* 
*/
class page_reports_agent_allagenttds extends Page{
	Public $title="All Agent TDS Report";
	function init(){
		parent::init();
		$this->vp = $this->add('VirtualPage');
		$this->vp->set([$this,'branchwiseTDS']);

		$till_date = $this->api->today;
		$from_date = '01-01-1970';
		if($this->app->stickyGET('to_date')) $till_date = $_GET['to_date'];
		if($this->app->stickyGET('from_date')) $from_date = $_GET['from_date'];


		$agent = $this->add('Model_AgentTDS');
		$form=$this->add('Form');
		$form->addField('DatePicker','from_date');
		$form->addField('DatePicker','to_date');
		$branch_m = $this->add('Model_Branch');
		$branch_field = $form->addField('dropdown','branch')->setEmptyText('All Branches');
		$branch_field->setModel($branch_m->addCondition('allow_login','true'));
		$form->addSubmit('GET List');
		$agent->addExpression('AccountNumber')->set(function($m,$q){
			return $m->refSQL('agent_id')->fieldQuery('AccountNumber');
		});
		$agent->addExpression('yearly_tds')->set(function($m,$q) use($from_date,$till_date){
			$fy = $this->app->getFinancialYear();
			$agtds = $this->add('Model_AgentTDS',['table_alias'=>'ytds']);

			$agtds->addCondition('created_at','>=',$from_date);
			$agtds->addCondition('created_at','<',$this->app->nextDate($till_date));
			$agtds->addCondition('agent_id',$m->getElement('agent_id'));
			if($_GET['branch']){
				
				$agtds->addCondition('branch_id',$_GET['branch']);
			}

			return $agtds->sum('tds');
		});
		$agent->addExpression('total_commissions')->set(function($m,$q) use($from_date,$till_date){
			$fy = $this->app->getFinancialYear();
			$agtds = $this->add('Model_AgentTDS',['table_alias'=>'ytds']);

			$agtds->addCondition('created_at','>=',$from_date);
			$agtds->addCondition('created_at','<',$this->app->nextDate($till_date));
			$agtds->addCondition('agent_id',$m->getElement('agent_id'));
			$agtds->addCondition('branch_id',$m->getElement('branch_id'));

			return $agtds->sum('total_commission');
		});


		if($_GET['branch']){
			$agent->addCondition('branch_id',$_GET['branch']);
		}

		$agent->_dsql()->group('agent_id');
		

		$grid = $this->add('Grid');




		$grid->setModel($agent,['agent','AccountNumber','branch','AccountNumber','yearly_tds','total_commissions',/*'tds','net_commission'*/]);
		$b = $grid->addColumn('Button','totaltds')->addClass('tds');

		$grid->setFormatter('totaltds','template')->setTemplate('<a class="account" href="#" data-agent_id="{$agent_id}">{$yearly_tds}</a>');
		$grid->js('click')->_selector('.account')->univ()->frameURL('BRANCHES Wise Agent TDS',[$this->vp->getURL(),'agent_id'=>$this->js()->_selectorThis()->data('agent_id'),'from_date'=>$from_date,'to_date'=>$till_date]);
		
		$grid->addPaginator(500);

		if($form->isSubmitted()){
			$send = array(
						'from_date'=>$form['from_date']?:0,'
						to_date'=>$form['to_date']?:0,'branch
						'=>$form['branch'],'filter'=>1);
			$grid->js()->reload($send)->execute();

		}	

	}

	function branchwiseTDS($page){
		$page->app->stickyGET('agent_id');
		$from_date = $page->app->stickyGET('from_date');
		$till_date = $page->app->stickyGET('to_date');

		// $page->add('View')->set($_GET['agent_id']);

		$agent = $this->add('Model_AgentTDS');
		$agent->addCondition('agent_id',$_GET['agent_id']);
		$agent->tryLoadAny();

		$agent->addExpression('total_tds')->set(function($m,$q) use($from_date,$till_date){
			$fy = $this->app->getFinancialYear();
			$agtds = $this->add('Model_AgentTDS',['table_alias'=>'ytds']);

			$agtds->addCondition('created_at','>=',$from_date);
			$agtds->addCondition('created_at','<',$this->app->nextDate($till_date));
			$agtds->addCondition('agent_id',$m->getElement('agent_id'));
			$agtds->addCondition('branch_id',$m->getElement('branch_id'));

			return $agtds->sum('tds');
		});





		$agent->_dsql()->group('branch_id');
		$grid = $page->add('Grid');
		$grid->setModel($agent/*,['branch','tds']*/);
		// $grid->removeColumn('branch');
		// $grid->removeColumn('branch_id');
		$grid->removeColumn('tds');
		$grid->removeColumn('total_commission');
		$grid->removeColumn('net_commission');
		$grid->removeColumn('related_account');
		$grid->removeColumn('created_at');

		// $grid->add


	}
}

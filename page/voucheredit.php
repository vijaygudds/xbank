<?php
class page_voucheredit extends Page {
	
	public $title = 'Voucher Edit';

	function init(){
		parent::init();
		$filter = $this->api->stickyGET('filter');
		$this->add('View_Warning')->set('Manual Voucher Edit');

		$form = $this->add('Form');
		$form->addField('line','apply_voucher_no')->validateNotNull();
		$form->add('View_Warning')->set('Enter Last Grater Vouher No ');
		$form->addField('DatePicker','from_date');
		$form->addField('DatePicker','to_date');
		$form->addField('Dropdown','edit_voucher')->setValueList(['Yes'=>'Yes','No'=>'No'])->setEmptyText('Please Select')->validateNotNull();
		$form->addSubmit('Go');
		if($filter){
			$edit_voucher = $this->api->stickyGET('edit_voucher');
			$this->api->stickyGET('apply_voucher');
			$this->api->stickyGET('from_date');
			$this->api->stickyGET('to_date');
			if($edit_voucher == 'Yes'){
				// $voucher_m = $this->add('Model_Transaction')
				// 			->addCondition('branch_id',$this->api->current_branch->id)
				// 			->addCondition('created_at','>=',$_GET['from_date'])
				// 			->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
				// 			->addCondition('voucher_no',$_GET['apply_voucher'])
				// 			->tryLoadAny();	
				// if($voucher_m->loaded()){
				// 	echo $voucher_m['voucher_no']." > ". $voucher_m['id'] ;
				// 	exit;
				// }else{
				// 	echo "Voucher not Found" ;
				// 	exit;
				// }			

				$m =  $this->add('Model_Transaction')
						->addCondition('branch_id',$this->api->current_branch->id)
						->addCondition('voucher_no','>',$_GET['apply_voucher'])
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
					$m->setOrder('created_at','asc');
					// $m->setLimit(150);
					// $this->add('View')->set($m->count()->getOne());
					// exit;
						// ->tryLoadAny();
				$sql_query= [];	
				$voucher = $_GET['apply_voucher'] + 1;
				set_time_limit(30000000);
				foreach ($m as $model) {
					// $model['voucher_no'] = $voucher;
					// $model->save();
					$sql_query[]= 'update transactions set voucher_no ='.$voucher.' where id='.$model->id.' ;'; 
					
					if(count($sql_query) >= 500){
						$q = implode(" ",$sql_query);
						// print_r($q);
						// exit;
						$this->api->db->dsql()->expr($q)->execute();
						$sql_query = [];
						$v= $this->add('View_Console')->set("first Voucher no" . $voucher);
					}	

					$voucher ++ ;

					// $this->add('View')->set($model['voucher_no']);	
				}
				if(count($sql_query) > 0){
						$q = implode(" ",$sql_query);
						$this->api->db->dsql()->expr($q)->execute();
						// $v= $this->add('View')->set("Voucher no" . $voucher);
					}		
				// $v->js(null,$this->app->js()->univ()->successMessage('Done'))->reload()->execute();
				// for ($i=1; $i <=$m->count()->getOne() ; $i++) { 
						
				// 	$m['voucher_no'] = $m['voucher_no'] +1;
				// 	$m->save();
				// 	var_dump($m);
				// 	throw new \Exception ($m->count()->getOne());
				// }

			}	
		}

		if($form->isSubmitted()){
			$form->js()->reload(
					array(
						'edit_voucher'=>$form['edit_voucher'],
						'from_date'=>($form['from_date'])?:0,
						'to_date'=>($form['to_date'])?:0,
						'apply_voucher'=>($form['apply_voucher_no'])?:0,
						'filter'=>1,
						)
					)->execute();
		}
	}
}		

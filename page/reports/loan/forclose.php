<?php

class page_reports_loan_forclose extends Page {
	public $title = "For Close Repots";
	function page_index() {
		// parent::init();

		$form = $this->add('Form');
		$loan_accoun = $form->addField('autocomplete/Basic', 'account_no')->validateNotNull();
		$loan_accoun->setModel('Account_Loan');

		$form->addSubmit('GET List');

		$grid = $this->add('Grid_AccountsBase');
		$grid->add('H3', null, 'grid_buttons')->set('For Close Report');

		$account_model = $this->add('Model_Account_Loan');

		if ($_GET['filter']) {
			if ($_GET['account_no']) {
				$account_no = $this->api->stickyGET('account_no');
			}

		} else {
			$account_no = -1;
		}

		$account_model->addCondition('id', $account_no);
		$dealer_join = $account_model->leftJoin('dealers', 'dealer_id');
		$dealer_join->addField('loan_panelty_per_day');

		$branch_j = $account_model->join('branches', 'branch_id');
		$branch_j->join('closings.branch_id')
			->addField('daily');

		$account_model->addExpression('first_premium_date')->set($account_model->refSQL('Premium')->setLimit(1)->setOrder('DueDate')->fieldQuery('DueDate'));
		$account_model->addExpression('last_premium_date')->set($account_model->refSQL('Premium')->setLimit(1)->setOrder('DueDate', 'desc')->fieldQuery('DueDate'));
		$account_model->addExpression('current_month_premium_date')->set(
			$account_model->refSQL('Premium')->setLimit(1)
				->addCondition('DueDate', '>=', date("Y-m-01", strtotime($this->api->today)))
				->addCondition('DueDate', '<', $this->api->nextDate(date('Y-m-t', strtotime($this->api->today))))
				->setOrder('DueDate')
				->fieldQuery('DueDate')
		);

		$account_model->addExpression('interest_rate')->set($account_model->refSQL('scheme_id')->fieldQuery('Interest'));
		$account_model->addExpression('premium_count')->set($account_model->refSQL('Premium')->count());
		$account_model->addExpression('PaneltyCharged')->set($account_model->refSQL('Premium')->sum('PaneltyCharged'));
		$account_model->addExpression('uncounted_panelty_days')->set(function ($m, $q) {
			return $q->expr('DATEDIFF("[0]",[1]) - 1', array($m->api->today, $m->getElement('daily'))); //$account_model->refSQL('Premium')->sum('PaneltyCharged'));
		});
		$account_model->addExpression('paid_premium_count')->set(function($m,$q)/*use($from_date,$to_date)*/{
			$p_m=$m->refSQL('Premium')
						->addCondition('PaidOn','<>',null);
			// if($from_date)
			// 	$p_m->addCondition('DueDate','>=',$from_date);
			// if($to_date)
			// 	$p_m->addCondition('DueDate','<',$m->api->nextDate($to_date));
			return $p_m->count();
		})->sortable(true);
		$account_model->addExpression('emi_amount')->set(function($m,$q){
			return $m->RefSQL('Premium')->setOrder('id','desc')->setLimit(1)->fieldQuery('Amount');
			return "'emi_amount'";
		});
		$account_model->addExpression('other_charges')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			// $tr_m->addCondition('transaction_type_id',[13, 46, 39,56,57,58]); // JV, TRA_VISIT_CHARGE, LegalChargeReceived
			// var_dump('MEMORANDUM_ACCOUNT_TRA_ARRAY');
			$tr_m->addCondition('transaction_type','<>',
				[
					'Visit Charge',
					'LEGAL NOTICE CHARGE RECEIVED',
					'CHEQUE RETURN 
					CHARGES RECEIVED',
					'VECHICLE GODOWN RENT RECEIVED',
					'LEGAL EXPENSES RECEIVED',
					'LEGAL NOTICE SENT FOR BIKE AUCTION CHARGE RECEIVED','FINAL RECOVERY NOTICE CHARGE RECEIVED',
					'CHEQUE RETURN NOTICE CHARGE RECEIVED',
					'INSURANCE PROCESSING FEES','SOCIETY NOTICE CHARGE RECEIVED',
					'NACH REGISTRATION FEES CHARGE RECEIVED',
					'NACH TRANSACTION FILE CANCELING CHARGE RECEIVED','NOC HANDLING CHARGE', 
					'FILE CANCEL CHARGE RECEIVED',
					'PRINTING & STATIONERY CHARGE RECEIVED',
					'GST OTHER CHARGE RECEIVED'
				]);
			// $tr_m->addCondition('transaction_type','<>','MEMORANDUM_TRA_ARRAY');
			$tr_m->addCondition('transaction_type','<>','LoanAccountOpen'); 
			$tr_m->addCondition('transaction_type','<>','InterestPostingsInLoanAccounts'); 
			$tr_m->addCondition('transaction_type','<>','PenaltyAccountAmountDeposit'); 
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('account_id',$q->getField('id'));

			return $tr_m->sum('amountDr');
		});

		$account_model->addExpression('other_received')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('transaction_type','<>','PenaltyAmountReceived');
			$tr_m->addCondition('transaction_type','<>',
								[
								'Visit Charge',
								'LEGAL NOTICE CHARGE RECEIVED',
								'CHEQUE RETURN CHARGES RECEIVED',
								'VECHICLE GODOWN RENT RECEIVED',
								'LEGAL EXPENSES RECEIVED',
								'LEGAL NOTICE SENT FOR BIKE AUCTION CHARGE RECEIVED',
								'FINAL RECOVERY NOTICE CHARGE RECEIVED',
								'CHEQUE RETURN NOTICE CHARGE RECEIVED',
								'SOCIETY NOTICE CHARGE RECEIVED',
								'INSURANCE PROCESSING FEES',
								'NACH REGISTRATION FEES CHARGE RECEIVED',
								'NACH TRANSACTION FILE CANCELING CHARGE RECEIVED',
								'NOC HANDLING CHARGE', 
								'FILE CANCEL CHARGE RECEIVED',
								'PRINTING & STATIONERY CHARGE RECEIVED',
								'GST OTHER CHARGE RECEIVED'
								]);
			$received = $tr_m->sum('amountCr');
			$premium_paid = $q->expr('([0]*[1])',[$m->getElement('paid_premium_count'),$m->getElement('emi_amount')]);
			$value = $q->expr('([0]-[1])',[$received,$premium_paid]);

			return $value;
		
		});

		$account_model->addExpression('remaning_other_amount')->set(function($m,$q){
			return $q->expr('(IFNULL([0],0))-(IFNULL([1],0))',[
					$m->getElement('other_charges'),
					$m->getElement('other_received'),
				]);
		});

		$account_model->addExpression('gst_amount_cr')->set(function($m,$q){
			$tr_m = $m->add('Model_Memorandum_TransactionRow',array('table_alias'=>'memo_amount_cr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$memo_amount_cr = $tr_m->sum('amountCr');
			return $memo_amount_cr;
		});
		$account_model->addExpression('gst_amount_dr')->set(function($m,$q){
			$tr_m = $m->add('Model_Memorandum_TransactionRow',array('table_alias'=>'memo_amount_dr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$memo_amount_dr = $tr_m->sum('amountDr');
			return $memo_amount_dr;
		});

		$account_model->addExpression('gst_due')->set(function($m,$q){
			$tr_m = $m->add('Model_Memorandum_TransactionRow',array('table_alias'=>'memo_amount_cr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$memo_amount_cr = $tr_m->sum('amountCr');
			$tr_m = $m->add('Model_Memorandum_TransactionRow',array('table_alias'=>'memo_amount_cr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$memo_amount_dr = $tr_m->sum('amountDr');
			// $premium_paid = $q->expr('([0]*[1])',[$memo_amount_cr,$m->getElement('emi_amount')]);
			return $q->expr('([0]-[1])',[$memo_amount_dr,$memo_amount_cr]);
		});


		// $account_model->addExpression('AmountCreditedTotal')->set($account_model->refSQL('TransactionRow')->sum('amountCr'));


		$account_premium_ref_m = $account_model->refSQL('Premium');
		$account_model->addExpression('AmountCreditedEMI')->set($account_premium_ref_m->sum($account_model->dsql()->expr('[0]*[1]', [$account_premium_ref_m->getElement('Amount'), $account_premium_ref_m->getElement('Paid')])));
		$account_model->addExpression('AmountCreditedPenalty')->set($account_model->refSQL('TransactionRow')->addCondition('transaction_type', TRA_PENALTY_AMOUNT_RECEIVED)->sum('amountCr'));

		$grid->addSno();
		$grid->setModel($account_model, array('AccountNumber', 'member','first_premium_date', 'last_premium_date','dealer','interest_rate','Amount','other_charges','other_received','remaning_other_amount','gst_amount_dr','gst_amount_cr','gst_due', 'current_month_premium_date','premium_count', 'daily', 'uncounted_panelty_days', 'loan_panelty_per_day', 'PaneltyCharged', 'AmountCreditedEMI', 'AmountCreditedPenalty', 'AmountCreditedOther', 'AmountCreditedTotal'));

		$grid->addMethod('format_total_panalty', function ($g, $f) {
			$g->current_row[$f] = $g->model['PaneltyCharged'] + $g->model['uncounted_panelty_days'] * $g->model['loan_panelty_per_day'];
		});

		$grid->addMethod('format_remaining_panelty', function ($g, $f) {
			$g->current_row[$f] = $g->current_row['total_panalty'] - $g->model['AmountCreditedPenalty'];
		});

		$grid->addMethod('format_monthly_interest_months', function ($g, $f) {
		});

		$grid->addMethod('format_AmountCreditedOther', function ($g, $f) {
			$g->current_row[$f] = $g->model['AmountCreditedTotal'] - ($g->model['AmountCreditedEMI'] + $g->model['AmountCreditedPenalty']);
		});

		$grid->addMethod('format_monthly_interest', function ($g, $f) {
			$first_premium = new MyDateTime($g->model['first_premium_date']);
			$today_date = $g->api->today;
			$today = new MyDateTime(
				strtotime($today_date) < strtotime($g->model['last_premium_date']) ?
				$today_date : $g->model['last_premium_date']
			);
			$interval = $today->diff($first_premium);
			$month = $interval->m + ($interval->y * 12);
			$months = $month + 1;

			$g->interests_for_months = $months;

			$g->monthly_interest = round(($g->model['Amount'] * ($g->model['interest_rate'] / 100) / 12) * ($g->model['premium_count'] + 1) / $g->model['premium_count']);
			$g->current_row[$f] = $g->monthly_interest * $g->interests_for_months;
		});

		$grid->addMethod('format_for_close_charge', function ($g, $f) {
			$g->forclose_charges = round(($g->model['premium_count'] - $g->interests_for_months) * ($g->monthly_interest * 40 / 100.00));
			$g->current_row[$f] = $g->forclose_charges;
		});

		$grid->addMethod('format_visit_charge', function ($g, $f) {
			$g->current_row[$f] = $g->model->ref('TransactionRow', 'model')->addCondition('transaction_type', TRA_VISIT_CHARGE)->sum('amountDr')->getOne();
		});

		$grid->addMethod('format_legal_charge', function ($g, $f) {
			$g->current_row[$f] = $g->model->ref('TransactionRow', 'model')->addCondition('transaction_type', TRA_LEGAL_CHARGE_RECEIVED)->sum('amountDr')->getOne();
		});

		// $grid->addMethod('format_other_charge', function ($g, $f) {
		// 	$g->current_row[$f] = $g->model->ref('TransactionRow', 'model')->addCondition('transaction_type', '<>', array(TRA_LEGAL_CHARGE_RECEIVED, TRA_VISIT_CHARGE, TRA_INTEREST_POSTING_IN_LOAN, TRA_PENALTY_ACCOUNT_AMOUNT_DEPOSIT, TRA_LOAN_ACCOUNT_OPEN))->sum('amountDr')->getOne();
		// });

		$grid->addMethod('format_time_over_charge', function ($g, $f) {
			if (strtotime($g->model['last_premium_date'] . '+1 month') > strtotime($g->api->today)) {
				$g->time_over_charge = 0;
				$g->current_row[$f] = 0;
				return;
			}

			$bal = $g->model->getOpeningBalance($g->api->nextDate(date("Y-m-d", strtotime($g->model['last_premium_date'] . '+1 month'))));

			$days = $g->api->my_date_diff($g->api->today, date("Y-m-d", strtotime($g->model['last_premium_date'] . '+1 month')));
			$g->time_over_charge = round(($bal['Dr'] - $bal['Cr']) * ($g->model['interest_rate'] / 100) / 365 * $days['days_total']);
			$g->current_row[$f] = $g->time_over_charge;
		});

		$grid->addMethod('format_AmountDebitedTotal', function ($g, $f) {
			$c = $g->current_row;
			$g->current_row[$f] = $g->model['Amount'] + $c['monthly_interest'] + $c['total_panalty']+ $c['other_charges'] + $g->model['gst_amount_dr'] + $c['for_close_charge'] + $c['time_over_charge'];
		});

		$grid->addMethod('format_AmountCreditedTotal', function ($g, $f) {
			$c = $g->current_row;
			$g->current_row[$f] = $g->model['AmountCreditedEMI'] + $g->model['AmountCreditedPenalty'] + $g->model['other_received']+ $g->model['gst_amount_cr'];
		});

		$grid->addMethod('format_for_close_amount', function ($g, $f) {
			$c = $g->current_row;
			$g->current_row[$f] = $c['AmountDebitedTotal'] - $c['AmountCreditedTotal'];
			/*$g->current_row[$f] = $g->model['Amount'] + $c['monthly_interest'] + $c['total_panalty'] + $c['for_close_charge'] + $c['visit_charge'] + $c['legal_charge'] + $c['other_charge'] + $c['time_over_charge'] + $g->model['gst_amount_dr']- $c['AmountCreditedTotal'];*/
		});



		$grid->addColumn('total_panalty', 'total_panalty');
		$grid->addColumn('monthly_interest_months', 'monthly_interest_months');
		$grid->addColumn('monthly_interest', 'monthly_interest');
		$grid->addColumn('remaining_panelty', 'remaining_panelty');
		// $grid->addColumn('visit_charge', 'visit_charge');
		// $grid->addColumn('legal_charge', 'legal_charge');
		// $grid->addColumn('other_charge', 'other_charge');
		$grid->addColumn('for_close_charge', 'for_close_charge');
		$grid->addColumn('time_over_charge', 'time_over_charge');
		$grid->addColumn('for_close_amount', 'for_close_amount');
		$grid->addColumn('AmountCreditedTotal', 'AmountCreditedTotal');
		$grid->addColumn('AmountDebitedTotal', 'AmountDebitedTotal');
		// $grid->addColumn('AmountCreditedOther', 'AmountCreditedOther');

		$grid->addOrder()
			->move('interest_rate','before','Amount')
			->move('monthly_interest','after','Amount')
			->move('total_panalty','after','monthly_interest')
			->move('AmountCreditedPenalty','after','total_panalty')
			->move('remaining_panelty','after','AmountCreditedPenalty')
			->move('for_close_charge','after','gst_due')
			->move('time_over_charge','after','for_close_charge')
			->move('AmountCreditedEMI','after','time_over_charge')
			->move('AmountDebitedTotal','after','AmountCreditedEMI')
			->move('AmountCreditedTotal','after','AmountDebitedTotal')
			->move('for_close_amount','after','AmountCreditedTotal')
			->now();

		$grid->removeColumn('current_month_premium_date');
		$grid->removeColumn('premium_count');
		$grid->removeColumn('daily');
		$grid->removeColumn('daily');
		$grid->removeColumn('uncounted_panelty_days');
		$grid->removeColumn('loan_panelty_per_day');
		$grid->removeColumn('PaneltyCharged');
		$grid->removeColumn('monthly_interest_months');

		$grid->addFormatter('member', 'wrap');

		$grid->addPaginator(5);

		if ($form->isSubmitted()) {
			$grid->js()->reload(array('account_no' => $form['account_no'], 'filter' => 1))->execute();
		}

	}

}


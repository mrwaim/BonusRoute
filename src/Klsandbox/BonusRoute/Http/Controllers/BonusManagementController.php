<?php

namespace Klsandbox\BonusRoute\Http\Controllers;

use App\Models\BonusCategory;
use App\Models\BonusMonthlyUserReport;
use App\Models\OrderMonthlyUserReport;
use App\Services\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\MessageBag;
use Klsandbox\OrderModel\Models\Order;
use Klsandbox\OrderModel\Models\OrderItem;
use Log;
use App;
use App\Http\Controllers\Controller;
use Klsandbox\BillplzRoute\Models\BillplzResponse;
use App\Models\Bonus;
use Klsandbox\ReportRoute\Models\MonthlyReport;
use Klsandbox\ReportRoute\Models\MonthlyUserReport;
use App\Models\PaymentsApprovals;
use App\Models\User;
use Klsandbox\ReportRoute\Services\ReportService;
use Auth;
use Carbon\Carbon;
use Input;
use Klsandbox\BonusModel\Services\BonusManager;
use Redirect;
use Session;
use Excel;

class BonusManagementController extends Controller
{
    /*
      |--------------------------------------------------------------------------
      | Home Controller
      |--------------------------------------------------------------------------
      |
      | This controller renders your application's "dashboard" for users that
      | are authenticated. Of course, you are free to change or remove the
      | controller as you wish. It is just here to get your app started!
      |
     */

    protected $bonusManager;

    /**
     * @var BonusCategory
     */
    private $bonusCategoryModel;

    /**
     * Create a new controller instance.
     *
     * @param BonusManager $bonusManager
     * @param BonusCategory $bonusCategoryModel
     */
    public function __construct(BonusManager $bonusManager, BonusCategory $bonusCategoryModel)
    {
        $this->middleware('auth');
        $this->bonusManager = $bonusManager;
        $this->bonusCategoryModel = $bonusCategoryModel;
    }

    //TODO: Secure this - bonus payout must match choices
    public function getChoosePayout($bonus_id, $bonus_payout_id)
    {
        $bonus = Bonus::find($bonus_id);
        Site::protect($bonus, 'Bonus');
        if (Auth::user()->id != $bonus->awarded_to_user_id) {
            App::abort(403, 'Unauthorized.');
        }

        if ($bonus->bonusPayout) {
            App::abort(403, 'Invalid..');
        }

        $bonus->workflow_status = 'ProcessedByReceiver';
        $bonus->bonus_payout_id = $bonus_payout_id;
        $bonus->save();

        Session::flash('success_message', 'The bonus payout has been selected.');

        return Redirect::to('/bonus-management/view/' . $bonus->id);
    }

    public function postCancelBonus()
    {
        \DB::transaction(function () use (&$bonus) {
            /**
             * @var Bonus $bonus
             */
            $bonusId = Input::get('bonus_id');
            $bonus = Bonus::find($bonusId);

            $bonus->cancelBonusAndChildBonuses();
            Session::flash('success_message', 'The bonus has been cancelled.');
        });

        return Redirect::to('/bonus-management/view/' . $bonus->id);
    }

    public function getView($bonusId)
    {
        $user = Auth::user();

        $bonus = Bonus::find($bonusId);
        Site::protect($bonus, 'Bonus');
        Site::protect($bonus->bonusType, 'Bonus type');
        $rc = new ReportService();
        $totalBonus = (object)$rc->getTotalBonusPayout();

        return view('bonus-route::view-bonus', [
            'user' => $user,
            'item' => $bonus,
            'totalBonus' => $totalBonus,
        ]);
    }

    public function getList($filter)
    {
        if ($filter == 'reorder') {
            User::adminGuard();

            $bonusCommands = [];
            /**
             * @var User $user
             */
            foreach (User::all() as $user) {
                if ($user->role->name == 'admin') {
                    continue;
                }

                foreach ($user->getEligibleBonusCategories() as $bonusCategory) {
                    $res = $this->bonusManager->resolveBonusCommandsForOrderItemUserDetails(0, new \Carbon\Carbon(), new OrderItem(), $user, $bonusCategory);
                    $bonusCommands = array_merge($bonusCommands, $res);
                }
            }

            return view('bonus-route::list-reorder-bonus')->with('bonus_commands', $bonusCommands);
        }

        if (Auth::user()->role->name == 'admin' && $filter == 'all') {
            $list = Bonus::with('bonusStatus', 'bonusPayout', 'bonusType')
                ->orderBy('created_at', 'DESC')
                ->paginate(20);
        } elseif ($filter == 'org') {
            $list = Bonus::where('awarded_by_organization_id', Auth::user()->organization_id)
                ->with('bonusStatus', 'bonusPayout', 'bonusType')
                ->orderBy('created_at', 'DESC')
                ->paginate(20);
        } else {
            $userIds = User::userIdsForFilter($filter);

            $list = Bonus::with('bonusStatus', 'bonusPayout', 'bonusType')
                ->whereIn('awarded_to_user_id', $userIds)
                ->orderBy('created_at', 'DESC')
                ->paginate(20);
        }

        $bonusCommands = [];
        if (Auth::user()->role->name != 'admin') {
            $bonusCommands = $this->bonusManager->resolveBonusCommandsForOrderItemUserDetails(0, new \Carbon\Carbon(), new OrderItem(), Auth::user(), BonusCategory::gStar());
        }

        $rc = new ReportService();
        $totalBonus = (object)$rc->getTotalBonusPayout();
        $topBonusUser = (object)$rc->getTopBonusUser();
        $bonusThisMonth = (object)$rc->getBonusThisMonth();

        return view('bonus-route::list-bonus')
            ->with('list', $list)
            ->with('bonusCommands', $bonusCommands)
            ->with('totalBonus', $totalBonus)
            ->with('bonusThisMonth', $bonusThisMonth)
            ->with('topBonusUser', $topBonusUser)
            ->with('filter', $filter);
    }

    public function getListOrders(Request $request, $year, $month, $is_hq, $organization_id)
    {
        if ($this->processExport($request, 'order-management.list-order-table-partial', func_get_args())) {
            return;
        }

        $monthlyUserReports = MonthlyReport::forOrganization($is_hq, $organization_id)
            ->where('year', '=', $year)
            ->where('month', '=', $month)
            ->first();

        $allOrders = [];
        foreach ($monthlyUserReports->userReports as $userReport) {
            /**
             * @var MonthlyUserReport $userReport
             */
            foreach (OrderMonthlyUserReport::where('monthly_user_report_id', '=', $userReport->id)->get() as $link) {
                $allOrders [] = $link->order_id;
            }
        }

        $allOrdersQuery = App\Models\Order::whereIn('id', $allOrders);
        $list = $allOrdersQuery
            ->paginate(500);

        return view('order-management.list-order')
            ->with('list', $list)
            ->with('restock', false)
            ->with('export', false)
            ->with('listTitle', 'Orders')
            ->with('filter', 'bonus-review');
    }

    public function getListUserBonuses(Request $request, $user_id, $monthly_user_report_id)
    {
        if ($this->processExport($request, 'bonus-route::list-bonus-table-partial', func_get_args())) {
            return;
        }

        $allBonuses = [];

        $monthlyReport = MonthlyReport::find($monthly_user_report_id);
        $userReport = $monthlyReport->userReports()->getQuery()->where('user_id', $user_id)->first();

        foreach (BonusMonthlyUserReport::where('monthly_user_report_id', '=', $userReport->id)->get() as $link) {
            $allBonuses [] = $link->bonus_id;
        }

        $allBonusesQuery = Bonus::whereIn('id', $allBonuses)
            ->whereAwardedToUserId($user_id);

        $list = $allBonusesQuery
            ->paginate(500);

        return view('bonus-route::list-bonus')
            ->with('list', $list)
            ->with('bonusCommands', null)
            ->with('totalBonus', null)
            ->with('bonusThisMonth', null)
            ->with('topBonusUser', null)
            ->with('show_awarded_to', true)
            ->with('export', false)
            ->with('filter', 'bonus-review');
    }

    public function getListBonuses(Request $request, $year, $month, $is_hq, $organization_id)
    {
        if ($this->processExport($request, 'bonus-route::list-bonus-table-partial', func_get_args())) {
            return;
        }

        $result = $this->getListData($year, $month, $is_hq, $organization_id, 'all');

        $allBonuses = [];
        foreach ($result->data as $userReport) {
            /**
             * @var MonthlyUserReport $userReport
             */
            foreach (BonusMonthlyUserReport::where('monthly_user_report_id', '=', $userReport->id)->get() as $link) {
                $allBonuses [] = $link->bonus_id;
            }
        }

        $allBonusesQuery = Bonus::whereIn('id', $allBonuses);
        $list = $allBonusesQuery
            ->paginate(500);

        return view('bonus-route::list-bonus')
            ->with('list', $list)
            ->with('bonusCommands', null)
            ->with('totalBonus', null)
            ->with('bonusThisMonth', null)
            ->with('show_awarded_to', true)
            ->with('topBonusUser', null)
            ->with('export', false)
            ->with('filter', 'bonus-review');
    }

    public function getTestListPayments($year, $month, $filter)
    {
        $resultList = [];
        $resultList [] = $this->getListData($year, $month, true, null, $filter);
        foreach (App\Models\Organization::all() as $organization) {
            $resultList [] = $this->getListData($year, $month, false, $organization->id, $filter);
        }

        $data = collect();
        foreach ($resultList as $result)
        {
            $data = $data->merge($result->data);
        }

        return view('bonus-route::list-payments')
            ->with('data', $data)
            ->with('payments_approvals', $result->payments_approvals_data)
            ->with('report', $result->report_id)
            ->with('user_type', $filter)
            ->with('filter', $filter);
    }

    public function getListPayments($year, $month, $is_hq, $organization_id, $filter)
    {
        $result = $this->getListData($year, $month, $is_hq, $organization_id, $filter);

        return view('bonus-route::list-payments')
            ->with('data', $result->data)
            ->with('payments_approvals', $result->payments_approvals_data)
            ->with('report', $result->report_id)
            ->with('user_type', $filter)
            ->with('filter', $filter);
    }

    public function getListData($year, $month, $is_hq, $organization_id, $filter)
    {
        $online_users = [];
        $start_date = new Carbon(date("$year-$month-01"));
        $end_date = new Carbon(date("$year-$month-01"));
        $end_date->endOfMonth();

        /**
         * @var MonthlyReport $monthlyReport
         */
        $monthlyReport = MonthlyReport::forOrganization($is_hq, $organization_id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (!$monthlyReport || !$monthlyReport->id) {
            return view('bonus-route::list-payments')
                ->with('data', [])
                ->with('user_type', $filter)
                ->with('payments_approvals', false);
        }

        $payments_approvals = PaymentsApprovals::where('monthly_report_id', $monthlyReport->id)
            ->select('user_id')
            ->where('approved_state', 'approve')
            ->orWhere('approved_state', 'reject')
            ->get()->toArray();

        $report_id = $monthlyReport->id;

        $report = $monthlyReport->userReports()->getQuery();

        $online_users_data = BillplzResponse::select('metadata_user_id')
            ->where('created_at', '>=', $start_date)
            ->where('created_at', '<=', $end_date)
            ->where('paid', true)
            ->groupBy('metadata_user_id')
            ->get()->toArray();

        foreach ($online_users_data as $val) {
            $online_users[] = $val['metadata_user_id'];
        }

        $report = $report
            ->where('monthly_user_reports.bonus_payout_cash', '>', 0)
            ->select(
                'monthly_user_reports.*',
                'payments_approvals.approved_state',
                'payments_approvals.payment_state'
            )
            ->leftJoin('payments_approvals', function ($join) {
                $join->on('monthly_user_reports.user_id', '=', 'payments_approvals.user_id');
                $join->on('monthly_user_reports.monthly_report_id', '=', 'payments_approvals.monthly_report_id');
            })
            ->groupBy('monthly_user_reports.user_id');

        switch ($filter) {
            case 'online':
                $report = $report->whereIn('monthly_user_reports.user_id', $online_users);
                break;
            case 'manual':
                $report = $report->whereNotIn('monthly_user_reports.user_id', $online_users);
                break;
            case 'all':
                break;
            default:
                assert(false, 'bad-filter');
                break;
        }

        $data = $report->get();

        $payments_approvals_data = $data->toArray();

        foreach ($payments_approvals_data as $key => $val) {
            foreach ($payments_approvals as $itm) {
                if ($itm['user_id'] === $val['user_id']) {
                    unset($payments_approvals_data[$key]);
                }
            }
        }

        return (object)compact('data', 'payments_approvals_data', 'report_id');
    }

    public function getBonusPaymentsList($filter)
    {
        if ($filter == 'pl') {
            $organization_id = Auth::user()->organization_id;
            $is_hq = false;
        } elseif ($filter == 'hq') {
            $organization_id = null;
            $is_hq = true;
        } else {
            $organization_id = App\Models\Organization::HQ()->id;
            $is_hq = false;
        }

        $data = MonthlyReport::getBonusPaymentsList($is_hq, $organization_id);

        return view('bonus-route::bonus-payments-list')
            ->with('data', $data)
            ->with('is_hq', $is_hq ? 1 : 0)
            ->with('organization_id', $organization_id ? $organization_id : 0);
    }

    private function validateUser($user_id)
    {
        $user = User::find($user_id);

        if (!preg_match('/^[0-9]+$/', $user->bank_account)) {
            Log::info("Unable to update user:$user->id - bank account not match");

            return false;
        }

        if (!preg_match('/^[0-9]{12}|stockist|agent$/', $user->ic_number)) {
            Log::info("Unable to update user:$user->id - bank account not match");

            return false;
        }

        if (!$user->bank_id) {
            Log::info("Unable to update user:$user->id - bank id is not set");

            return false;
        }

        return true;
    }

    public function getSetPaymentsApprovals()
    {
        $validate = \Validator::make(Input::all(), [
            'id' => 'required|numeric',
            'status' => 'required',
            'user_type' => 'required',
        ]);

        if ($validate->messages()->count()) {
            App::abort(422, 'Invalid data');
        }

        /**
         * @var MonthlyUserReport $report
         */
        $report = MonthlyUserReport::find(Input::get('id'));

        $user_type = Input::get('user_type');

        if ($user_type == 'all') {
            $user_type = $report->online_payer ? 'online' : 'manual';
        }

        if (empty($report)) {
            App::abort(422, 'Invalid data');
        }

        assert($report->monthlyReport->admin_id == Auth::user()->id);

        if (!$this->validateUser($report->user_id) && Input::get('status') == 'approve') {
            $messages = new MessageBag();
            $messages->add('user', 'User payment details not valid');

            return back()->withErrors($messages);
        }

        $payments_approvals = PaymentsApprovals::where('user_id', $report->user_id)
            ->where('monthly_report_id', $report->monthly_report_id)->first();

        if (empty($payments_approvals)) {
            PaymentsApprovals::create([
                'user_id' => $report->user_id,
                'approved_state' => Input::get('status'),
                'payment_state' => 'unpaid',
                'monthly_report_id' => $report->monthly_report_id,
                'user_type' => $user_type,
            ]);
        } else {
            $payments_approvals->approved_state = Input::get('status');
            $payments_approvals->user_type = $user_type;
            $payments_approvals->save();
        }

        return back();
    }

    public function getExcel($monthly_report_id, $type)
    {
        $file_name = 'bonus_' . date('m') . '_' . date('y') . '_' . $type;

        $data_excel = $this->getExportData($monthly_report_id, $type);

        Excel::create($file_name, function ($excel) use ($file_name, $data_excel) {

            $excel->sheet($file_name, function ($sheet) use ($data_excel) {
                $sheet->fromArray($data_excel, null, 'A1', false, false);
            });

        })->export('xls');
    }

    public function getExport($monthly_report_id, $type)
    {
        $data_excel = $this->getExportData($monthly_report_id, $type);

        array_shift($data_excel);

        $total = array_sum(array_pluck($data_excel, 3));

        $content = \View::make('bonus-route::export')
            ->withDataExcel($data_excel)
            ->withTotal($total);

        // Set the name of the text file
        $filename = 'bonus_' . date('m') . '_' . date('y') . '_' . $type . '.txt';

        // Set headers necessary to initiate a download of the textfile, with the specified name
        $headers = array(
            'Content-Type' => 'plain/txt',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Content-Length' => strlen($content),
        );

        return \Response::make($content, 200, $headers);
    }

    public function getExportData($monthly_report_id, $type, $test = false)
    {
        $query = MonthlyReport::find($monthly_report_id)
            ->userPaymentsApprovals()
            ->with(['user', 'user.bank']);

        if (!$test)
        {
            $query = $query->where('approved_state', 'approve');
        }

        if ($type != 'all') {
            $query = $query->where('user_type', $type);
        }

        $payments_approvals = $query->get();

        $header = [
            'Payment Mode',
            'Value Date',
            'Customer Reference Number',
            'Transaction Amount (RM)',
            'Credit Account Number',
            'Beneficiary Name 1',
            'Beneficiary Name 2',
            'Beneficiary Name 3',
            'ID No (New IC, Old IC, Passport, Business Registration No)',
            'Beneficiary Bank Code',
            'Email',
            'Advice Detail',
            'Debit Description',
            'Credit Description',
        ];

        $data_excel[0] = $header;

        foreach ($payments_approvals as $item) {
            $monthlyUserReport = MonthlyUserReport::where('monthly_report_id', '=', $monthly_report_id)
                ->where('user_id', '=', $item->user_id)
                ->where('online_payer', '=', $item->user_type == 'online' ? 1 : 0)
                ->first();

            if (!$monthlyUserReport) {
                continue;
            }

            if ($monthlyUserReport->bonus_payout_cash == 0) {
                continue;
            }

            $date = $monthlyUserReport->monthlyReport->month . '/' . $monthlyUserReport->monthlyReport->year;

            @$data_excel[] = [
                ($item->user->bank->swift_code === 'MBBEMYKL') ? 'IT' : 'IG',
                date('dmY'),
                '',
                $monthlyUserReport->bonus_payout_cash,
                // TODO: Validate on input
                preg_replace('/[^0-9]+/', '', $item->user->bank_account),
                $item->user->getBankAccountName(),
                'NOT APPLICABLE',
                'NOT APPLICABLE',
                $item->user->getBankAccountId(),
                ($item->user->bank->swift_code === 'MBBEMYKL') ? '' : $item->user->bank->swift_code,
                $item->user->email,
                config('export_excel.advice_detail') . $date,
                config('export_excel.debit_description') . $date . ' for ' . $item->user_id,
                config('export_excel.credit_description') . $date,
            ];
        }

        return $data_excel;
    }

    public function getTestSetApprovalsAll($year, $month, $type)
    {
        $resultList = [];
        $resultList [] = $this->getListData($year, $month, true, null, $type);
        foreach (App\Models\Organization::all() as $organization) {
            $resultList [] = $this->getListData($year, $month, false, $organization->id, $type);
        }

        foreach ($resultList as $result)
        {
            $this->approveAll($type, $result->report_id, true);
        }

        return 'OK';
    }


    public function postSetApprovalsAll()
    {
        $validate = \Validator::make(Input::all(), [
            'monthly_report_id' => 'required|numeric',
            'type' => 'required|in:online,manual,all',
            'approved_state' => 'required|in:Approve All,Reject All'
        ]);

        if ($validate->messages()->count()) {
            App::abort(422, 'Invalid data');
        }

        $monthly_report_id = Input::get('monthly_report_id');
        $type = Input::get('type');

        $this->approvalAll($type, $monthly_report_id, Input::get('approved_state'));

        flash()->success('Success!', 'Payment approvals has been updated');

        return back();
    }

    /**
     * Display page list bonus categories
     *
     * @return string
     */
    public function getListBonusCategories()
    {
        return view('bonus-route::list-bonus-categories')
            ->with('bonusCategories', BonusCategory::all());
    }

    /**
     * Display page list bonus categories
     *
     * @return string
     */
    public function getCreateBonusCategory()
    {
        return view('bonus-route::create-bonus-category');
    }

    /**
     * Save new bonus category
     */
    public function postCreateBonusCategory()
    {
        $input = Input::all();

        $messages = \Validator::make($input, [
            'name' => 'required|unique:bonus_categories,name,NULL,id',
            'friendly_name' => 'required',
            'description' => 'required',
        ]);

        if ($messages->messages()->count()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($messages);
        }

        $this->bonusCategoryModel->create([
            'name' => $input['name'],
            'friendly_name' => $input['friendly_name'],
            'description' => $input['description'],
        ]);

        Session::flash('success_message', 'Bonus category has been created.');

        return Redirect::to('/bonus-management/list-bonus-categories');
    }

    public function getDeleteBonusCategory($id)
    {
        $bonusCategory = BonusCategory::find($id);

        if (!$bonusCategory) {
            App::abort(500, 'Category not found');
        }

        $bonusCategory->delete();

        Session::flash('success_message', 'Bonus Category has been deleted.');

        return Redirect::to('/bonus-management/list-bonus-categories');
    }

    public function paymentState()
    {
        $validate = \Validator::make(Input::all(), [
            'id' => 'required|numeric',
            'status' => 'required',
            'user_type' => 'required',
        ]);

        if ($validate->messages()->count()) {
            App::abort(422, 'Invalid data');
        }

        $report = MonthlyUserReport::find(Input::get('id'));

        if (empty($report)) {
            App::abort(422, 'Invalid data');
        }

        assert($report->monthlyReport->admin_id == Auth::user()->id);

        if (!$this->validateUser($report->user_id) && Input::get('status') == 'approve') {
            $messages = new MessageBag();
            $messages->add('user', 'User payment details not valid');

            return back()->withErrors($messages);
        }

        $payments_approvals = PaymentsApprovals::where('user_id', $report->user_id)
            ->where('monthly_report_id', $report->monthly_report_id)->first();

        if (empty($payments_approvals)) {
            PaymentsApprovals::create([
                'user_id' => $report->user_id,
                'approved_state' => 'not-reviewed',
                'monthly_report_id' => $report->monthly_report_id,
                'user_type' => Input::get('user_type'),
                'payment_state' => Input::get('status'),
            ]);
        } else {
            $payments_approvals->payment_state = Input::get('status');
            $payments_approvals->user_type = Input::get('user_type');
            $payments_approvals->save();
        }

        return back();
    }

    public function getTestBillplzBulkPay($year, $month, $type)
    {
        $resultList = [];
        $resultList [] = $this->getListData($year, $month, true, null, $type);
        foreach (App\Models\Organization::all() as $organization) {
            $resultList [] = $this->getListData($year, $month, false, $organization->id, $type);
        }

        $ids = [];
        foreach ($resultList as $result) {
            $ids[] = $result->report_id;
        }

        $this->getBillplzExcel($type, $ids);
    }

    public function getBillplzBulkPay($monthlyReportId, $type)
    {
        $this->getBillplzExcel($type, [$monthlyReportId]);
    }

    /**
     * @param $type
     * @param $monthly_report_id
     * @param $approvedState
     * @param bool $test
     */
    private function approvalAll($type, $monthly_report_id, $approvedState, $test = false)
    {
        if($approvedState == 'Approve All'){
            $approvedState = 'approve';
        }else{
            $approvedState = 'reject';
        }

        if ($type == 'all') {
            $types = ['online', 'manual'];
        } else {
            $types = [$type];
        }

        foreach ($types as $type) {

            $reports = MonthlyUserReport::
            where('monthly_report_id', $monthly_report_id)
                ->where('bonus_payout_cash', '>', 0)
                ->get();

            /**
             * @var MonthlyUserReport $itm
             */
            foreach ($reports as $itm) {
                if (!$test)
                {
                    assert($itm->monthlyReport->admin_id == \Auth::user()->id);
                }

                if ($itm->online_payer && $type == 'manual') {
                    continue;
                }

                if (!$itm->online_payer && $type == 'online') {
                    continue;
                }

                if (!$this->validateUser($itm->user_id)) {
                    continue;
                }

                $payments_approvals = PaymentsApprovals::where('user_id', $itm->user_id)
                    ->where('monthly_report_id', $monthly_report_id)
                    ->where('user_type', $type)
                    ->first();

                if (!$payments_approvals) {
                    PaymentsApprovals::create([
                        'user_id' => $itm->user_id,
                        'approved_state' => $approvedState,
                        'monthly_report_id' => $monthly_report_id,
                        'user_type' => $type,
                    ]);
                } else {
                    $payments_approvals->approved_state = $approvedState;
                    $payments_approvals->user_type = $type;
                    $payments_approvals->save();
                }
            }
        }
    }

    /**
     * @param $type
     * @param $ids
     */
    private function getBillplzExcel($type, $ids)
    {
        $data_excel = [];
        foreach ($ids as $monthlyReportId) {
            $data = $this->getExportData($monthlyReportId, $type, true);
            array_shift($data);

            $monthlyReport = MonthlyReport::find($monthlyReportId);

            foreach ($data as $item) {
                $user = User::whereEmail($item[10])->first();
                assert($user);

                @$data_excel[] = new Fluent([
                    'name' => $item[5],
                    'total' => $item[3],
                    'email' => $item[10],
                    'phone' => $user->getPhone(),
                    'bank_account' => $item[4],
                    'id_number' => $item[8],
                    'due_date' => Carbon::now()->format('d/m/Y'),
                    'description' => $monthlyReport->is_hq ? $item[11] : preg_replace('/GSTAR/', 'BioKare', $item[11]),
                ]);
            }
        }


        Excel::create('billplz-bulk-pay', function ($excel) use ($data_excel) {
            $excel->sheet('Sheet1', function ($sheet) use ($data_excel) {
                $sheet->loadView('bonus-route::bulk-pay', ['users' => $data_excel]);
            });
        })->export('xls');
    }
}

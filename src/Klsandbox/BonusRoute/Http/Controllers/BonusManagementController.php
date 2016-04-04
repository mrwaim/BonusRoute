<?php

namespace Klsandbox\BonusRoute\Http\Controllers;

use Illuminate\Support\MessageBag;
use Log;
use App;
use App\Http\Controllers\Controller;
use Klsandbox\BillplzRoute\Models\BillplzResponse;
use App\Models\Bonus;
use Klsandbox\ReportRoute\Models\MonthlyReport;
use Klsandbox\ReportRoute\Models\MonthlyUserReport;
use App\Models\Order;
use App\Models\PaymentsApprovals;
use App\Models\User;
use Klsandbox\ReportRoute\Services\ReportService;
use Auth;
use Carbon\Carbon;
use Input;
use Klsandbox\BonusModel\Services\BonusManager;
use Klsandbox\SiteModel\Site;
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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BonusManager $bonusManager)
    {
        $this->middleware('auth');
        $this->bonusManager = $bonusManager;
    }

    //TODO: Secure this - bonus payout must match choices
    public function getChoosePayout($bonus_id, $bonus_payout_id)
    {
        $bonus = Bonus::find($bonus_id);
        Site::protect($bonus);

        if (Auth::user()->id != $bonus->awarded_to_user_id) {
            App::abort(403, "Unauthorized.");
        }

        if ($bonus->bonusPayout) {
            App::abort(403, "Invalid..");
        }

        $bonus->workflow_status = 'ProcessedByReceiver';
        $bonus->bonus_payout_id = $bonus_payout_id;
        $bonus->save();

        Session::flash('success_message', 'The bonus payout has been selected.');

        return Redirect::to('/bonus-management/view/' . $bonus->id);
    }

    public function postCancelBonus()
    {
        User::adminGuard();

        \DB::transaction(function () use (&$bonus) {
            $bonusId = Input::get('bonus_id');
            $bonus = Bonus::find($bonusId);

            Site::protect($bonus, 'Bonus');

            $bonus->cancelBonusAndChildBonuses();
            Session::flash('success_message', 'The bonus has been cancelled.');
        });

        return Redirect::to('/bonus-management/view/' . $bonus->id);
    }


    public function getView($bonusId)
    {
        $user = Auth::user();

        $bonus = Bonus::find($bonusId);
        Site::protect($bonus, "Bonus");

        Site::protect($bonus->bonusType, "Bonus Type");

        return view('bonus-route::view-bonus', [
            'user' => $user,
            'item' => $bonus,
        ]);
    }

    public function getList($filter)
    {
        if ($filter == 'reorder') {
            User::adminGuard();

            $bonusCommands = [];
            foreach (User::forSite()->get() as $user) {
                if ($user->role->name == 'admin') {
                    continue;
                }

                $res = $this->bonusManager->resolveBonusCommandsForOrderUserDetails(0, new \Carbon\Carbon(), new Order(), $user);
                $bonusCommands = array_merge($bonusCommands, $res);
            }

            return view('bonus-route::list-reorder-bonus')->with('bonus_commands', $bonusCommands);
        }

        if (Auth::user()->role->name == 'admin') {
            $list = Bonus::forSite()
                ->orderBy('created_at', 'DESC')
                ->paginate(20);
        } else {
            $userIds = User::userIdsForFilter($filter);

            $list = Bonus::forSite()
                ->whereIn('awarded_to_user_id', $userIds)
                ->orderBy('created_at', 'DESC')
                ->paginate(20);
        }

        $bonusCommands = [];
        if (Auth::user()->role->name != 'admin') {
            $bonusCommands = $this->bonusManager->resolveBonusCommandsForOrderUserDetails(0, new \Carbon\Carbon(), new Order(), Auth::user());
        }

        $rc = new ReportService();
        $totalBonus = (object)$rc->getTotalBonusPayout();
        $topBonusUser = (object)$rc->getTopBonusUser();

        return view('bonus-route::list-bonus')
            ->with('list', $list)
            ->with('bonusCommands', $bonusCommands)
            ->with('totalBonus', $totalBonus)
            ->with('topBonusUser', $topBonusUser);
    }

    public function getListPayments($year, $month, $filter)
    {
        $online_users = [];
        $start_date = new Carbon(date("$year-$month-01"));
        $end_date = new Carbon(date("$year-$month-01"));
        $end_date->endOfMonth();

        $report = MonthlyReport::where('year', $year)
            ->where('month', $month)->first();

        if (empty($report)) {
            return view('bonus-route::list-payments')
                ->with('data', [])
                ->with('user_type', $filter)
                ->with('payments_approvals', false);
        }

        $payments_approvals = PaymentsApprovals::forSite()->where('monthly_report_id', $report->id)
            ->select('user_id')
            ->where('approved_state', 'approve')
            ->orWhere('approved_state', 'reject')
            ->get()->toArray();

        $report_id = $report->id;
        $report = $report->userReports();

        $online_users_data = BillplzResponse
            ::forSite()
            ->select('metadata_user_id')
            ->where('created_at', '>=', $start_date)
            ->where('created_at', '<=', $end_date)
            ->where('paid', true)
            ->groupBy('metadata_user_id')
            ->get()->toArray();

        foreach ($online_users_data as $val) {
            $online_users[] = $val['metadata_user_id'];
        }

        $report = $report->where('monthly_user_reports.bonus_payout_cash', '>', 0)
            ->select(
                'monthly_user_reports.*',
                'payments_approvals.approved_state'
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
            default:
                $report = $report->whereNotIn('monthly_user_reports.user_id', $online_users);
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

        return view('bonus-route::list-payments')
            ->with('data', $data)
            ->with('user_type', $filter)
            ->with('payments_approvals', $payments_approvals_data)
            ->with('report', $report_id)
            ->with('filter', $filter);
    }

    public function getBonusPaymentsList()
    {
        $data = MonthlyReport::getBonusPaymentsList();

        return view('bonus-route::bonus-payments-list')
            ->with('data', $data);
    }

    private function validateUser($user_id)
    {
        $user = User::find($user_id);

        if (!preg_match('/^[0-9]+$/', $user->bank_account))
        {
            Log::info("Unable to update user:$user->id - bank account not match");
            return false;
        }

        if (!preg_match('/^[0-9]{12}$/', $user->ic_number))
        {
            Log::info("Unable to update user:$user->id - bank account not match");
            return false;
        }

        if (!$user->bank_id)
        {
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

        $report = MonthlyUserReport::find(Input::get('id'));

        if (empty($report)) {
            App::abort(422, 'Invalid data');
        }

        if (!$this->validateUser($report->user_id) && Input::get('status') == 'approve')
        {
            $messages = new MessageBag();
            $messages->add('user', "User payment details not valid");

            return back()->withErrors($messages);
        }

        $payments_approvals = PaymentsApprovals::forSite()->where('user_id', $report->user_id)
            ->where('monthly_report_id', $report->monthly_report_id)->first();

        if (empty($payments_approvals)) {
            PaymentsApprovals::create([
                'user_id' => $report->user_id,
                'approved_state' => Input::get('status'),
                'monthly_report_id' => $report->monthly_report_id,
                'user_type' => Input::get('user_type')
            ]);
        } else {
            $payments_approvals->approved_state = Input::get('status');
            $payments_approvals->user_type = Input::get('user_type');
            $payments_approvals->save();
        }

        return back();
    }

    public function getExcel($monthly_report_id, $type)
    {
        $file_name = "bonus_" . date('m') . "_" . date('y') . "_" . $type;

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
        $filename = "bonus_" . date('m') . "_" . date('y') . "_" . $type . ".txt";

        // Set headers necessary to initiate a download of the textfile, with the specified name
        $headers = array(
            'Content-Type' => 'plain/txt',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Content-Length' => strlen($content),
        );

        return \Response::make($content, 200, $headers);
    }

    public function getExportData($monthly_report_id, $type)
    {
        $payments_approvals = MonthlyReport::find($monthly_report_id)
            ->userPaymentsApprovals()
            ->with(['user', 'user.bank'])
            ->where('user_type', $type)
            ->where('approved_state', 'approve')
            ->get();

        $header = [
            'Payment Mode', 'Value Date', 'Customer Reference Number', 'Transaction Amount (RM)',
            'Credit Account Number', 'Beneficiary Name 1', 'Beneficiary Name 2', 'Beneficiary Name 3',
            'ID No (New IC, Old IC, Passport, Business Registration No)', 'Beneficiary Bank Code', 'Email',
            'Advice Detail', 'Debit Description', 'Credit Description'
        ];

        $data_excel[0] = $header;

        foreach ($payments_approvals as $item) {
            $monthlyUserReport = MonthlyUserReport::where('monthly_report_id', '=', $monthly_report_id)
                ->where('user_id', '=', $item->user_id)
                ->first();

            if ($monthlyUserReport->bonus_payout_cash == 0)
            {
                continue;
            }

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
                config('export_excel.advice_detail') . date('Y/m'),
                config('export_excel.debit_description') . date('Y/m') . ' for ' . $item->user_id,
                config('export_excel.credit_description') . date('Y/m')
            ];
        }

        return $data_excel;
    }

    public function postSetApprovalsAll()
    {
        $validate = \Validator::make(Input::all(), [
            'monthly_report_id' => 'required|numeric',
            'type' => 'required|in:online,manual',
        ]);

        if ($validate->messages()->count()) {
            App::abort(422, 'Invalid data');
        }

        $monthly_report_id = Input::get('monthly_report_id');
        $type = Input::get('type');

        $reports = MonthlyUserReport::where('monthly_report_id', $monthly_report_id)->get();

        foreach ($reports as $itm) {

            if (!$this->validateUser($itm->user_id))
            {
                continue;
            }

            $payments_approvals = PaymentsApprovals::where('user_id', $itm->user_id)
                ->where('monthly_report_id', $monthly_report_id)
                ->where('user_type', $type)
                ->first();

            if (empty($payments_approvals)) {
                PaymentsApprovals::create([
                    'user_id' => $itm->user_id,
                    'approved_state' => 'approve',
                    'monthly_report_id' => $monthly_report_id,
                    'user_type' => $type
                ]);
            } else {
                $payments_approvals->approved_state = 'approve';
                $payments_approvals->user_type = $type;
                $payments_approvals->save();
            }
        }

        return back();
    }
}

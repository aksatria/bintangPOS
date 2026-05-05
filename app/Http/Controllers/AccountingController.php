<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Support\AppliesBranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    use AppliesBranchScope;

    public function journals(Request $request)
    {
        $entries = $this->applyBranchScope(JournalEntry::query(), $request->user())
            ->with(['lines.account', 'creator:id,name'])
            ->latest('posted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.accounting-journals', compact('entries'));
    }

    public function ledger(Request $request)
    {
        $accountId = (int) $request->query('account_id', 0);
        $accounts = Account::query()->where('is_active', true)->orderBy('code')->get();
        $lines = JournalLine::query()
            ->with(['account', 'entry'])
            ->whereHas('entry', fn ($query) => $this->applyBranchScope($query, $request->user()))
            ->when($accountId > 0, fn ($query) => $query->where('account_id', $accountId))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.accounting-ledger', compact('accounts', 'accountId', 'lines'));
    }

    public function reports(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $accounts = Account::query()
            ->leftJoin('journal_lines', 'accounts.id', '=', 'journal_lines.account_id')
            ->leftJoin('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(journal_lines.debit), 0) as debit_total'),
                DB::raw('COALESCE(SUM(journal_lines.credit), 0) as credit_total')
            )
            ->where('accounts.is_active', true)
            ->where(function ($query) use ($request, $from, $to) {
                $query->whereNull('journal_entries.id')
                    ->orWhere(function ($entryQuery) use ($request, $from, $to) {
                        $this->applyBranchScope($entryQuery, $request->user(), 'journal_entries');
                        $entryQuery
                            ->when($from, fn ($q) => $q->whereDate('journal_entries.posted_at', '>=', $from))
                            ->when($to, fn ($q) => $q->whereDate('journal_entries.posted_at', '<=', $to));
                    });
            })
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get()
            ->map(function ($account) {
                $debit = (float) $account->debit_total;
                $credit = (float) $account->credit_total;
                $normalDebit = in_array((string) $account->type, ['asset', 'expense', 'contra_income'], true);
                $account->balance = $normalDebit ? $debit - $credit : $credit - $debit;

                return $account;
            });

        $trialDebit = (float) $accounts->sum('debit_total');
        $trialCredit = (float) $accounts->sum('credit_total');
        $income = (float) $accounts->where('type', 'income')->sum('balance');
        $contraIncome = (float) $accounts->where('type', 'contra_income')->sum('balance');
        $expense = (float) $accounts->whereIn('type', ['expense'])->sum('balance');
        $contraExpense = (float) $accounts->where('type', 'contra_expense')->sum('balance');
        $netIncome = $income - $contraIncome - $expense + $contraExpense;
        $assets = (float) $accounts->where('type', 'asset')->sum('balance');
        $liabilities = (float) $accounts->where('type', 'liability')->sum('balance');
        $equityClosing = $assets - $liabilities;

        $accountCodeHints = [
            '1101' => 'Kas operasional (uang tunai di toko/cabang).',
            '1102' => 'Bank (saldo rekening operasional).',
            '1103' => 'Piutang pelanggan (tagihan penjualan kredit).',
            '1201' => 'Persediaan barang dagang (stok bernilai biaya).',
            '1301' => 'PPN Masukan (pajak pembelian yang bisa dikreditkan).',
            '2101' => 'Hutang usaha supplier (tagihan pembelian belum lunas).',
            '2102' => 'PPN Keluaran (pajak penjualan yang terutang).',
            '4101' => 'Penjualan (pendapatan utama).',
            '4102' => 'Potongan penjualan (pengurang pendapatan).',
            '5101' => 'Harga pokok penjualan (biaya barang terjual).',
            '5201' => 'Beban ongkir pembelian (jika ongkir dibebankan langsung).',
            '5301' => 'Diskon pembelian (pengurang beban pembelian).',
        ];

        $typeLabels = [
            'asset' => 'Aset',
            'liability' => 'Kewajiban',
            'income' => 'Pendapatan',
            'expense' => 'Beban',
            'contra_income' => 'Kontra Pendapatan',
            'contra_expense' => 'Kontra Beban',
        ];

        return view('admin.accounting-reports', compact(
            'accounts',
            'from',
            'to',
            'trialDebit',
            'trialCredit',
            'income',
            'contraIncome',
            'expense',
            'contraExpense',
            'netIncome',
            'assets',
            'liabilities',
            'equityClosing',
            'accountCodeHints',
            'typeLabels'
        ));
    }
}

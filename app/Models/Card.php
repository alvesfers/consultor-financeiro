<?php

namespace App\Models;

use App\Support\BrBusinessDay;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'brand',
        'limit_amount',
        'close_day',
        'due_day',
        'payment_account_id',
    ];

    protected $casts = [
        'limit_amount' => 'decimal:2',
        'close_day' => 'integer',
        'due_day' => 'integer',
    ];

    // ---- Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Data de FECHAMENTO (ajustada para próximo dia útil, se cair em fim de semana).
     */
    public function closeDateForMonth(int $year, int $month): Carbon
    {
        return BrBusinessDay::businessDayForYearMonth($year, $month, (int) $this->close_day);
    }

    /**
     * Data de VENCIMENTO (mês seguinte ao fechamento, ajustada para próximo dia útil).
     */
    public function dueDateForMonth(int $year, int $month): Carbon
    {
        $next = Carbon::create($year, $month, 1)->addMonth();

        return BrBusinessDay::businessDayForYearMonth(
            (int) $next->year,
            (int) $next->month,
            (int) $this->due_day
        );
    }

    /**
     * Ciclo atual de fatura baseado em $ref.
     * - start: dia seguinte ao fechamento anterior (00:00)
     * - end: dia do fechamento do ciclo (23:59:59)
     * - invoice_month: YYYY-MM do fechamento
     * - close_date: data de fechamento (00:00)
     * - due_date: data de vencimento correspondente
     */
    public function currentCycle(?Carbon $ref = null): array
    {
        $ref ??= now();

        $closeThis = $this->closeDateForMonth((int) $ref->year, (int) $ref->month);

        if ($ref->lessThanOrEqualTo($closeThis)) {
            // ainda estamos no ciclo que fecha neste mês
            $prev = $ref->copy()->subMonth();
            $closePrev = $this->closeDateForMonth((int) $prev->year, (int) $prev->month);

            $start = $closePrev->copy()->addDay()->startOfDay();
            $end = $closeThis->copy()->endOfDay();
            $invoiceMonth = $closeThis->format('Y-m');
            $closeDate = $closeThis->copy()->startOfDay();
        } else {
            // já passou o fechamento deste mês; ciclo vigente fecha no mês seguinte
            $next = $ref->copy()->addMonth();
            $closeNext = $this->closeDateForMonth((int) $next->year, (int) $next->month);

            $start = $closeThis->copy()->addDay()->startOfDay();
            $end = $closeNext->copy()->endOfDay();
            $invoiceMonth = $closeNext->format('Y-m');
            $closeDate = $closeNext->copy()->startOfDay();
        }

        $due = $this->dueDateForMonth(
            (int) substr($invoiceMonth, 0, 4),
            (int) substr($invoiceMonth, 5, 2)
        );

        return [
            'start' => $start,
            'end' => $end,
            'invoice_month' => $invoiceMonth,
            'close_date' => $closeDate,
            'due_date' => $due,
        ];
    }

    /**
     * Define em qual fatura (YYYY-MM) uma compra na data $purchaseDate cairá.
     */
    public function invoiceMonthForPurchase(Carbon $purchaseDate): string
    {
        $closeThis = $this->closeDateForMonth((int) $purchaseDate->year, (int) $purchaseDate->month);

        if ($purchaseDate->lessThanOrEqualTo($closeThis)) {
            return $closeThis->format('Y-m');
        }

        $next = $purchaseDate->copy()->addMonth();
        $closeNext = $this->closeDateForMonth((int) $next->year, (int) $next->month);

        return $closeNext->format('Y-m');
    }
}

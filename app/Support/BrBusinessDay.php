<?php

namespace App\Support;

use Carbon\Carbon;

class BrBusinessDay
{
    /**
     * Retorna o PRÓXIMO dia útil se a data cair em fim de semana.
     * (Simples: só pula sábado/domingo. Se quiser, dá pra plugar feriados depois.)
     */
    public static function nextBusinessDay(Carbon $date): Carbon
    {
        $d = $date->copy();
        while (in_array($d->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true)) {
            $d->addDay();
        }

        return $d;
    }

    /**
     * Constrói uma data YYYY-MM-{day}, clamp no fim do mês se day > último dia,
     * e aplica regra de “próximo dia útil”.
     */
    public static function businessDayForYearMonth(int $year, int $month, int $day): Carbon
    {
        $base = Carbon::create($year, $month, 1)->startOfDay();
        $maxDay = $base->daysInMonth;
        $safeDay = min($day, $maxDay);

        $d = Carbon::create($year, $month, $safeDay)->startOfDay();

        return self::nextBusinessDay($d);
    }
}

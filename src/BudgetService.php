<?php
/**
 * Created by PhpStorm.
 * User: recca0120
 * Date: 2018/11/10
 * Time: 3:33 PM
 */

namespace App;


use Carbon\Carbon;

class BudgetService
{
    private $budgets;

    /**
     * BudgetService constructor.
     * @param BudgetRepository $budgets
     */
    public function __construct(IBudgetRepository $budgets)
    {
        $this->budgets = $budgets;
    }

    public function totalAmount(Carbon $startDate, Carbon $endDate)
    {
        $budgets = $this->budgets->getAll();

        if ($this->sameMonth($startDate, $endDate) === true) {
            return $this->getBudget($budgets, $startDate, $endDate);
        }

        $dates = [
            [$startDate, $startDate->copy()->lastOfMonth()],
            [$endDate->copy()->firstOfMonth(), $endDate],
        ];

        $diffInMonths = $endDate->diffInMonths($startDate) - 1;
        for ($i = 1; $i <= $diffInMonths; $i++) {
            $nextMonth = $startDate->copy()->addMonth($i);
            $dates[] = [
                $nextMonth->copy()->firstOfMonth(),
                $nextMonth->copy()->lastOfMonth(),
            ];
        }

        return array_reduce($dates, function ($sum, $date) use ($budgets) {
            return $sum + $this->getBudget($budgets, $date[0], $date[1]);
        }, 0);
    }

    private function sameMonth(Carbon $startDate, Carbon $endDate)
    {
        return $startDate->format('Y-m') === $endDate->format('Y-m');
    }

    private function getBudget($budgets, Carbon $startDate, Carbon $endDate)
    {
        $monthlyBudget = $this->getMonthlyBudget($budgets, $startDate);

        if ($monthlyBudget === 0) {
            return 0;
        }

        $daysOfMonth = $this->getDaysOfMonth($startDate);
        $diffInDays = $this->diffInDays($startDate, $endDate);

        return $daysOfMonth === $diffInDays
            ? $monthlyBudget
            : ($monthlyBudget / $daysOfMonth) * $diffInDays;
    }

    private function getMonthlyBudget($budgets, Carbon $date)
    {
        return $budgets[$date->format('Y-m')];
    }

    private function getDaysOfMonth(Carbon $date)
    {
        return $this->diffInDays($date->copy()->startOfMonth(), $date->copy()->endOfMonth());
    }

    private function diffInDays(Carbon $startDate, Carbon $endDate)
    {
        return $endDate->diffInDays($startDate) + 1;
    }
}
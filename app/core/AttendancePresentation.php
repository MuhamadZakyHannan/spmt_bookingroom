<?php


class AttendancePresentation {
    private const BASE_BUTTON_CLASS = 'w-full px-4 py-2 border-2 rounded-xl font-bold transition text-xs flex items-center justify-center gap-1.5 shadow-sm';

    private const ACTIONS = [
        'check_in' => [
            'label' => 'Check-in',
            'icon' => 'fas fa-sign-in-alt',
            'variant_class' => 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-300 dark:bg-emerald-600 dark:hover:bg-emerald-700 dark:text-white dark:border-emerald-500',
        ],
        'check_out' => [
            'label' => 'Check-out',
            'icon' => 'fas fa-sign-out-alt',
            'variant_class' => 'bg-violet-50 hover:bg-violet-100 text-violet-700 border-violet-300 dark:bg-violet-600 dark:hover:bg-violet-700 dark:text-white dark:border-violet-500',
        ],
    ];

    public static function actions(): array {
        $actions = [];

        foreach (self::ACTIONS as $name => $definition) {
            $definition['button_class'] = self::BASE_BUTTON_CLASS . ' ' . $definition['variant_class'];
            unset($definition['variant_class']);
            $actions[$name] = $definition;
        }

        return $actions;
    }

    public static function action(string $name): array {
        return self::actions()[$name] ?? [];
    }
}

<?php

use Carbon\Carbon;
use Illuminate\Support\Str;

if (!function_exists('as_currency')) {
    function as_currency($val, $decimals = 0, $decimal_separator = '.', $thousands_separator = ' '): string
    {
        if ($decimals === true) {
            $decimals = 0;
            $suffix = '&nbsp;р.';
        } else {
            $suffix = '';
        }

        return $val ? number_format($val, $decimals, $decimal_separator, $thousands_separator) . $suffix : '';
    }
}

if (!function_exists('as_date')) {
    function as_date($val, $format = 'd.m.Y \г\.'): string
    {
        if (!$val instanceof Carbon) {
            $val = Carbon::parse($val)->setTimezone(config('app.timezone'));
        }

        /** @var Carbon $val */
        return $val->format($format);
    }
}

if (!function_exists('as_time')) {
    function as_time($val, $format = 'H:i'): string
    {
        if (!$val instanceof Carbon) {
            $val = Carbon::parse($val);
        }

        /** @var Carbon $val */
        return $val->format($format);
    }
}

if (!function_exists('as_phone')) {
    function as_phone($val): string
    {
        return $val ? '+' . preg_replace('/[^0-9]/', '', $val) : '';
    }
}

if (!function_exists('as_phonelink')) {
    function as_phonelink($val, $protocol = 'tel'): string
    {
        return sprintf(
            '<a href="%s:%s" target="_blank">%s</a>',
            $protocol,
            '+' . preg_replace('/[^0-9]/', '', $val),
            $val
        );
    }
}

if (!function_exists('as_list')) {
    function as_list($val): string
    {
        if (empty($val)) {
            return '';
        }
        if (is_array($val)) {
            array_walk($val, static function (&$el, $key) {
                $el = is_array($el) ? as_list($el) : $el;
                $el = sprintf('<li>%s: %s</li>', Str::ucfirst($key), $el);
            });
            return sprintf("<ul>%s</ul>", implode($val));
        }
        return $val;
    }
}

if (!function_exists('clean_phone')) {
    function clean_phone($val): string
    {
        $val = $val ? preg_replace('/\D/', '', $val) : '';
        if (strlen($val) === 11 && $val[0] === '8') {
            $val[0] = '7';
        }
        return $val;
    }
}

if (!function_exists('as_link')) {
    function as_link($link, $label, $targetBlank = false): string
    {
        return sprintf('<a href="%s"%s>%s</a>', $link, $targetBlank ? ' target="_blank"' : '', $label);
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input(&$val)
    {
        $isArray = is_array($val);
        $val = (array) $val;

        array_walk($val, static function (&$el) {
            if (is_string($el) && !is_numeric($el)) {
                $el = strip_tags($el);
            }
        });

        return $isArray ? $val : reset($val);
    }
}

if (!function_exists('float2rationals')) {
    function float2rationals(float $n, $tolerance = 1.e-6): string
    {
        if ($n == 0) {
            return '0';
        }
        $tolerance = max(1.e-6, min(1, $tolerance));
        $num = 1;
        $denom = 0;
        $h2 = 0;
        $k2 = 1;
        $b = 1 / $n;
        do {
            $b = 1 / $b;
            $a = floor($b);
            $aux = $num;
            $num = $a * $num + $h2;
            $h2 = $aux;
            $aux = $denom;
            $denom = $a * $denom + $k2;
            $k2 = $aux;
            $b -= $a;
        } while (abs($n - $num / $denom) > $n * $tolerance);

        return "$num/$denom";
    }
}

if (!function_exists('rationals2float')) {
    function rationals2float(string $rationalN, $roundLevel = 5): ?float
    {
        $rationalN = trim($rationalN);

        if (empty($rationalN)) {
            return 0;
        }

        $parts = explode('/', $rationalN, 2);
        if (empty($parts[0]) || empty($parts[1]) || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return null;
        }

        $nominator = (int)$parts[0];
        $denominator = (int)$parts[1];

        if ($denominator <= 0) {
            return null;
        }

        $roundLevel = max(0, min(12, $roundLevel));

        return round($nominator / $denominator, $roundLevel);
    }
}

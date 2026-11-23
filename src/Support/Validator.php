<?php
declare(strict_types=1);

namespace KD\Support;

/**
 * Allow-list input validation.
 *
 *   Validator::validate($_POST, ['email' => 'required|email|max:190']);
 *
 * Returns [field => first error message]. Empty optional fields skip the other rules.
 */
final class Validator
{
    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $list  = explode('|', $ruleString);
            $label = ucfirst(str_replace('_', ' ', $field));

            $empty = $value === null || $value === '' || $value === [];
            if ($empty) {
                if (in_array('required', $list, true)) {
                    $errors[$field] = "{$label} is required.";
                }
                continue;
            }
            if (!is_string($value) && !is_int($value)) {
                $errors[$field] = "{$label} is invalid.";
                continue;
            }
            $value = (string) $value;

            foreach ($list as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $error = self::check($name, $arg, $value, $label);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }
        return $errors;
    }

    private static function check(string $rule, ?string $arg, string $value, string $label): ?string
    {
        switch ($rule) {
            case 'required':
                return null;
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "{$label} must be a valid email address.";
            case 'min':
                return mb_strlen($value) >= (int) $arg ? null : "{$label} must be at least {$arg} characters.";
            case 'max':
                return mb_strlen($value) <= (int) $arg ? null : "{$label} must be at most {$arg} characters.";
            case 'int':
                return preg_match('/^-?\d{1,9}$/', $value) ? null : "{$label} must be a whole number.";
            case 'between':
                [$lo, $hi] = array_map('intval', explode(',', (string) $arg));
                $n = (int) $value;
                return ($n >= $lo && $n <= $hi) ? null : "{$label} must be between {$lo} and {$hi}.";
            case 'in':
                return in_array($value, explode(',', (string) $arg), true) ? null : "{$label} has an invalid value.";
            case 'phone':
                return preg_match('/^\+?[0-9 ()-]{7,20}$/', $value) ? null : "{$label} must be a valid phone number.";
            case 'url':
                if (preg_match('#^/(?![/\\\\])#', $value)) {
                    return null;
                }
                return (preg_match('#^https?://#i', $value) && filter_var($value, FILTER_VALIDATE_URL))
                    ? null : "{$label} must be a site path (/shop) or an http(s) link.";
            case 'slug':
                return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? null : "{$label} may use lowercase letters, numbers and dashes only.";
            case 'money':
                return Money::parse($value) !== null ? null : "{$label} must be an amount like 499 or 499.50.";
            case 'date':
                $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                return ($d && $d->format('Y-m-d') === $value) ? null : "{$label} must be a valid date.";
            case 'text':
                // Single line, no control characters.
                return preg_match('/[\x00-\x1F\x7F]/', $value) ? "{$label} contains invalid characters." : null;
            default:
                throw new \LogicException("Unknown validation rule '{$rule}'.");
        }
    }
}

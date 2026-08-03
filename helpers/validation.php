<?php
function validate_required(array $fields, array $data): array
{
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[$field] = sprintf('%s is required.', $label);
        }
    }
    return $errors;
}

function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_passwords_match(string $password, string $confirm): bool
{
    return $password === $confirm;
}

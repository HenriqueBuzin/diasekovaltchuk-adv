<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\ValidPhone;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nome' => ['bail', 'required', 'string', 'min:3', 'max:120'],
            'email' => ['bail', 'required', 'string', 'max:160', 'email:rfc'],
            'telefone' => ['bail', 'required', 'string', new ValidPhone],
            'assunto' => ['bail', 'required', 'string', 'min:3', 'max:160', 'not_regex:/[\r\n]/'],
            'mensagem' => ['bail', 'required', 'string', 'min:10', 'max:1200'],
            'website' => ['nullable', 'string', 'max:255'],
            'captchaProvider' => ['nullable', 'string', 'max:32'],
            'captchaToken' => ['nullable', 'string', 'max:4096'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.*' => trans('site.valid_name'),
            'email.*' => trans('site.valid_email'),
            'telefone.*' => trans('site.valid_phone'),
            'assunto.not_regex' => trans('site.invalid_subject_chars'),
            'assunto.*' => trans('site.valid_subject'),
            'mensagem.*' => trans('site.valid_message'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $trimmed = [];
        foreach (['nome', 'email', 'telefone', 'assunto', 'mensagem', 'website', 'captchaProvider', 'captchaToken'] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $trimmed[$key] = trim($value);
            }
        }
        $this->merge($trimmed);
    }

    protected function failedValidation(Validator $validator): never
    {
        $errors = array_values(array_unique(array_merge(...array_values($validator->errors()->toArray()))));
        $message = implode(' ', $errors);

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $errors,
        ], 400));
    }
}
